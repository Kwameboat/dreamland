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
    ];

    public function init()
    {
        parent::init();
        $params = Yii::$app->params;
        if (!empty($params['dreamlandLiveServerUrl'])) {
            $this->serverUrl = (string) $params['dreamlandLiveServerUrl'];
        }
        if (!empty($params['dreamlandLiveSignalingUrl'])) {
            $this->signalingUrl = (string) $params['dreamlandLiveSignalingUrl'];
        }
        if (!empty($params['dreamlandLiveSecret'])) {
            $this->internalSecret = (string) $params['dreamlandLiveSecret'];
        }
        if (!empty($params['dreamlandLiveIceServers']) && is_array($params['dreamlandLiveIceServers'])) {
            $this->iceServers = $params['dreamlandLiveIceServers'];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function clientConfig(UserLiveHistory $live, string $role = 'viewer'): array
    {
        return [
            'provider' => 'dreamland',
            'signaling_url' => rtrim($this->signalingUrl, '/'),
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
        ]);
        return is_array($result) && !empty($result['ok']);
    }

    public function closeRoom(int $liveId): bool
    {
        $result = $this->request('DELETE', '/internal/rooms/' . $liveId);
        return is_array($result) && !empty($result['ok']);
    }

    public function isHealthy(): bool
    {
        $result = $this->request('GET', '/health', null, 1);
        return is_array($result) && !empty($result['ok']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function request(string $method, string $path, ?array $body = null, int $timeout = 5): ?array
    {
        $url = rtrim($this->serverUrl, '/') . $path;
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
