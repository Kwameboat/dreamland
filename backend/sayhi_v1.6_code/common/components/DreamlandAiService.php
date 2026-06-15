<?php

namespace common\components;

use Yii;
use yii\base\Component;

/**
 * Dreamland AI — smart feed, caption assist, signup safety (proxies moderation agent).
 */
class DreamlandAiService extends Component
{
    public function isEnabled(): bool
    {
        return Yii::$app->has('dreamlandModeration') && Yii::$app->dreamlandModeration->isHealthy();
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        $healthy = $this->isEnabled();
        $agentStatus = null;
        $health = null;
        if ($healthy) {
            $health = Yii::$app->dreamlandModeration->getHealth();
            $raw = $this->agentRequest('GET', '/api/ai/status', null, 3);
            $agentStatus = is_array($raw) ? $raw : null;
        }
        $gemini = is_array($health) ? ($health['gemini'] ?? []) : ($agentStatus['gemini'] ?? []);

        return [
            'enabled' => $healthy,
            'provider' => !empty($gemini['configured']) ? 'google-gemini' : 'dreamland-ghana-ai',
            'primary_provider' => $agentStatus['primary_provider'] ?? ($gemini['configured'] ? 'google-gemini' : 'dreamland-lexicon'),
            'capabilities' => $agentStatus['capabilities'] ?? [
                'multilingual_moderation',
                'smart_feed_ranking',
                'caption_assist',
                'signup_safety',
                'content_safety_queue',
            ],
            'locales' => $agentStatus['locales'] ?? [],
            'gemini' => [
                'configured' => (bool) ($gemini['configured'] ?? false),
                'model' => (string) ($gemini['model'] ?? Yii::$app->params['dreamlandGeminiModel'] ?? 'gemini-2.0-flash'),
                'multimodal' => (bool) ($gemini['multimodal'] ?? true),
            ],
        ];
    }

    /**
     * @return array{ok:bool,message?:string,decision?:string,score?:int,summary?:string}
     */
    public function checkSignupText(string $name, string $username): array
    {
        $blob = trim($name . ' ' . $username);
        if ($blob === '') {
            return ['ok' => false, 'message' => 'Enter a display name and username.'];
        }

        if (Yii::$app->has('dreamlandSafety')) {
            $scan = Yii::$app->dreamlandSafety->runLocalTextScan($blob);
            if (empty($scan['passed'])) {
                return [
                    'ok' => false,
                    'message' => $scan['summary'] ?? 'Dreamland AI blocked this name or username for safety.',
                    'decision' => $scan['decision'] ?? 'block',
                    'score' => (int) ($scan['score'] ?? 0),
                ];
            }
        }

        return ['ok' => true, 'decision' => 'allow', 'summary' => 'Approved by Dreamland AI'];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    public function suggestCaptions(array $payload): ?array
    {
        $result = $this->agentRequest('POST', '/api/ai/caption-suggest', $payload, 20);
        if (is_array($result) && !empty($result['ok'])) {
            return $result;
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $desc = trim((string) ($payload['description'] ?? ''));
        $base = $title ?: $desc ?: 'Dreamland moment';
        return [
            'ok' => true,
            'captions' => [
                "{$base} — watch on Dreamland",
                "New drop: {$base}",
            ],
            'hashtags' => ['#Dreamland', '#Ghana', '#Reels'],
            'hook' => "{$base} — watch on Dreamland",
            'provider' => 'dreamland-ai-fallback',
        ];
    }

    /**
     * SQL expression for AI feed ordering (engagement + recency + optional genre).
     */
    public function feedOrderExpression(?int $categoryId = null): string
    {
        $genreBonus = $categoryId > 0
            ? ' + IF(post.category_id = ' . (int) $categoryId . ', 0.35, 0)'
            : '';
        return '(LOG(1 + post.total_view) * 0.18'
            . ' + LOG(1 + post.total_like) * 0.52'
            . ' + LOG(1 + post.total_share) * 0.28'
            . ' + GREATEST(0, 1 - (UNIX_TIMESTAMP() - post.created_at) / 604800) * 0.45'
            . $genreBonus
            . ') DESC';
    }

    /**
     * @param array<int, array<string, mixed>> $posts
     * @param array<string, mixed> $preferences
     * @return array<int, array<string, mixed>>
     */
    public function rankFeedPosts(array $posts, array $preferences = []): array
    {
        if (!$posts) {
            return [];
        }

        $payload = [
            'posts' => array_map(static function ($post) {
                return [
                    'id' => (int) ($post['id'] ?? 0),
                    'title' => (string) ($post['title'] ?? ''),
                    'description' => (string) ($post['description'] ?? ''),
                    'total_view' => (int) ($post['total_view'] ?? 0),
                    'total_like' => (int) ($post['total_like'] ?? 0),
                    'total_share' => (int) ($post['total_share'] ?? 0),
                    'category_id' => (int) ($post['category_id'] ?? 0),
                    'created_at' => (int) ($post['created_at'] ?? 0),
                ];
            }, $posts),
            'preferences' => $preferences,
        ];

        $result = $this->agentRequest('POST', '/api/ai/rank-feed', $payload, 8);
        if (!is_array($result) || empty($result['posts']) || !is_array($result['posts'])) {
            return $posts;
        }

        $byId = [];
        foreach ($posts as $post) {
            $byId[(int) ($post['id'] ?? 0)] = $post;
        }

        $ranked = [];
        foreach ($result['posts'] as $row) {
            $id = (int) ($row['id'] ?? 0);
            if (isset($byId[$id])) {
                $ranked[] = $byId[$id];
                unset($byId[$id]);
            }
        }

        foreach ($byId as $remaining) {
            $ranked[] = $remaining;
        }

        return $ranked;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function agentRequest(string $method, string $path, ?array $body = null, int $timeout = 5): ?array
    {
        if (!Yii::$app->has('dreamlandModeration')) {
            return null;
        }
        $agent = Yii::$app->dreamlandModeration;
        $url = rtrim($agent->agentUrl, '/') . $path;
        $headers = "Accept: application/json\r\nContent-Type: application/json\r\nX-Dreamland-Secret: {$agent->internalSecret}\r\n";
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
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
