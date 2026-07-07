<?php

namespace common\components;

use api\modules\v1\models\UserLiveHistory;
use Yii;
use yii\base\Component;

/**
 * Registers Dreamland live rooms with the self-hosted WebRTC SFU (no Agora).
 */
class DreamlandLiveRtcService extends Component
{
    /** @var string Internal HTTP base URL (PHP → live-server) */
    public $serverUrl = 'http://127.0.0.1:4443';

    /** @var string Public URL browsers use for Socket.IO signaling */
    public $signalingUrl = 'http://localhost:4443';

    /** @var string Shared secret for /internal/* routes */
    public $internalSecret = 'dreamland-live-dev-secret';

    /** @var array<int, array<string, mixed>> */
    public $iceServers = [
        ['urls' => 'stun:stun.l.google.com:19302'],
        ['urls' => 'stun:stun1.l.google.com:19302'],
        ['urls' => 'stun:stun.cloudflare.com:3478'],
    ];

    public function init()
    {
        parent::init();
        $params = Yii::$app->params;
        if (!empty($params['dreamlandLiveServerUrl'])) {
            $url = (string) $params['dreamlandLiveServerUrl'];
            // Render cannot carry WebRTC media — prefer Fly when misconfigured.
            if (stripos($url, 'onrender.com') !== false) {
                $url = 'https://dreamland-live.fly.dev';
            }
            $this->serverUrl = $url;
        }
        if (!empty($params['dreamlandLiveSignalingUrl'])) {
            $url = (string) $params['dreamlandLiveSignalingUrl'];
            if (stripos($url, 'onrender.com') !== false) {
                $url = 'https://dreamland-live.fly.dev';
            }
            $this->signalingUrl = $url;
        }
        if (!empty($params['dreamlandLiveSecret'])) {
            $this->internalSecret = (string) $params['dreamlandLiveSecret'];
        }
        if (!empty($params['dreamlandLiveIceServers']) && is_array($params['dreamlandLiveIceServers'])) {
            $this->iceServers = $params['dreamlandLiveIceServers'];
        }
    }

    /**
     * Public WebRTC edge URL — browsers connect here directly (TikTok-style SFU signaling).
     */
    public function edgeSignalingUrl(): string
    {
        $url = rtrim($this->signalingUrl, '/');
        if (stripos($url, 'onrender.com') !== false) {
            $url = 'https://dreamland-live.fly.dev';
        }
        return $url;
    }

    /**
     * URL browsers should use for Socket.IO.
     * Production uses Fly.io edge directly (WebSocket); same-origin proxy is fallback only.
     */
    public function browserSignalingUrl(): string
    {
        $params = Yii::$app->params;
        if (!empty($params['dreamlandLiveBrowserSignalingUrl'])) {
            return rtrim((string) $params['dreamlandLiveBrowserSignalingUrl'], '/');
        }
        $pwa = getenv('DREAMLAND_PWA_URL') ?: getenv('PWA_URL') ?: '';
        if ($pwa !== '' && stripos($pwa, 'dreamlandgh.app') !== false) {
            return $this->edgeSignalingUrl();
        }
        return $this->edgeSignalingUrl();
    }

    /**
     * @return array<string, mixed>
     */
    public function clientConfig(UserLiveHistory $live, string $role = 'viewer'): array
    {
        $edge = $this->edgeSignalingUrl();
        $pwa = getenv('DREAMLAND_PWA_URL') ?: getenv('PWA_URL') ?: '';
        $proxy = ($pwa !== '' && stripos($pwa, 'dreamlandgh.app') !== false)
            ? rtrim($pwa, '/') . '/live-socket'
            : '';

        return [
            'provider' => 'dreamland',
            'signaling_url' => $edge,
            'signaling_url_direct' => $edge,
            'signaling_url_proxy' => $proxy,
            'live_id' => (int) $live->id,
            'token' => (string) $live->token,
            'role' => $role,
            'ice_servers' => $this->iceServers,
        ];
    }

    public function registerRoom(int $liveId, int $hostUserId, string $token): bool
    {
        $result = $this->request('POST', '/internal/rooms', [
            'liveId' => $liveId,
            'hostUserId' => $hostUserId,
            'token' => $token,
        ], 8);
        return is_array($result) && !empty($result['ok']);
    }

    public function registerRoomWithRetry(int $liveId, int $hostUserId, string $token, int $attempts = 2): bool
    {
        for ($i = 0; $i < $attempts; $i++) {
            if ($this->registerRoom($liveId, $hostUserId, $token)) {
                return true;
            }
            if ($i < $attempts - 1) {
                usleep(400000);
            }
        }
        return false;
    }

    public function verifyInternalAuth(): bool
    {
        $result = $this->request('GET', '/internal/ping', null, 4);
        return is_array($result) && !empty($result['ok']);
    }

    public function closeRoom(int $liveId): bool
    {
        $result = $this->request('DELETE', '/internal/rooms/' . $liveId);
        return is_array($result) && !empty($result['ok']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function roomStatus(int $liveId): ?array
    {
        return $this->request('GET', '/internal/rooms/' . $liveId . '/status', null, 4);
    }

    public function isHealthy(): bool
    {
        $timeout = stripos((string) ($this->serverUrl ?? ''), 'fly.dev') !== false ? 6 : 4;
        $result = $this->request('GET', '/health', null, $timeout);
        return is_array($result) && !empty($result['ok']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function request(string $method, string $path, ?array $body = null, int $timeout = 5): ?array
    {
        $url = rtrim($this->serverUrl, '/') . $path;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $headers = [
                'Accept: application/json',
                'Content-Type: application/json',
                'X-Dreamland-Secret: ' . $this->internalSecret,
            ];
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => min(4, $timeout),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_FOLLOWLOCATION => false,
            ]);
            if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
            $raw = curl_exec($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            if ($raw === false || $errno !== 0) {
                Yii::warning("Dreamland live-server unreachable: {$method} {$url}", __METHOD__);
                return null;
            }
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : null;
        }

        $headers = "Accept: application/json\r\nContent-Type: application/json\r\nX-Dreamland-Secret: {$this->internalSecret}\r\n";

        $opts = [
            'http' => [
                'method' => $method,
                'header' => $headers,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ];

        if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $opts['http']['content'] = json_encode($body);
        }

        $ctx = stream_context_create($opts);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            Yii::warning("Dreamland live-server unreachable: {$method} {$url}", __METHOD__);
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
