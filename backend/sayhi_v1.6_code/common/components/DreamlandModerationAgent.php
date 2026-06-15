<?php

namespace common\components;

use Yii;
use yii\base\Component;

/**
 * Client for Dreamland inbuilt AI moderation agent (Ghana local languages).
 */
class DreamlandModerationAgent extends Component
{
    public $agentUrl = 'http://127.0.0.1:4444';

    public $internalSecret = 'dreamland-mod-dev-secret';

    public $blockThreshold = 70;

    public $reviewThreshold = 40;

    public function init()
    {
        parent::init();
        $params = Yii::$app->params;
        if (!empty($params['dreamlandModerationAgentUrl'])) {
            $this->agentUrl = (string) $params['dreamlandModerationAgentUrl'];
        }
        if (!empty($params['dreamlandModerationSecret'])) {
            $this->internalSecret = (string) $params['dreamlandModerationSecret'];
        }
        if (isset($params['dreamlandModerationBlockThreshold'])) {
            $this->blockThreshold = (int) $params['dreamlandModerationBlockThreshold'];
        }
        if (isset($params['dreamlandModerationReviewThreshold'])) {
            $this->reviewThreshold = (int) $params['dreamlandModerationReviewThreshold'];
        }
    }

    public function isHealthy(): bool
    {
        $result = $this->request('GET', '/health', null, 1);
        return is_array($result) && !empty($result['ok']);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    public function moderateContent(array $payload): ?array
    {
        $payload['blacklist'] = $payload['blacklist'] ?? \common\models\LocalBlacklistKeyword::getActiveKeywords();
        $result = $this->request('POST', '/api/moderate/content', $payload, 45);
        if (!is_array($result)) {
            return null;
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    public function checkText(array $payload): ?array
    {
        $payload['blacklist'] = $payload['blacklist'] ?? \common\models\LocalBlacklistKeyword::getActiveKeywords();
        return $this->request('POST', '/api/ai/check-text', $payload, 20);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getHealth(): ?array
    {
        return $this->request('GET', '/health', null, 3);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getConfig(): ?array
    {
        return $this->request('GET', '/api/config', null, 2);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function request(string $method, string $path, ?array $body = null, int $timeout = 5): ?array
    {
        $url = rtrim($this->agentUrl, '/') . $path;
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
            Yii::warning("Dreamland moderation agent unreachable: {$method} {$url}", __METHOD__);
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
