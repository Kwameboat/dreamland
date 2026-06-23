<?php

namespace common\components;

use common\models\LocalBlacklistKeyword;
use common\models\Post;
use common\models\SafetyScanQueue;
use common\helpers\DreamlandMediaUrl;
use Yii;
use yii\base\Component;

/**
 * Multimodal AI safety sensor for Dreamland content pipeline.
 */
class DreamlandSafetyPipeline extends Component
{
    /**
     * @param Post|\api\modules\v1\models\Post|int $post
     */
    public function enqueueVideoScan($post, array $textParts = [])
    {
        $post = $this->normalizePost($post);
        $mediaUrl = $this->resolveMediaUrl($post);
        $textPayload = json_encode([
            'title' => (string) $post->title,
            'description' => (string) $post->description,
            'tags' => $textParts['tags'] ?? [],
        ]);

        $job = SafetyScanQueue::enqueue($post->id, $mediaUrl ?: '', $textPayload);
        $post->appraisal_status = 'pending_safety';
        $post->safety_scan_job_id = (string) $job->id;
        $post->save(false);

        return $job;
    }

    public function runLocalTextScan($text)
    {
        if (Yii::$app->has('dreamlandModeration')) {
            $agent = Yii::$app->dreamlandModeration->checkText(['text' => (string) $text]);
            if (is_array($agent)) {
                $decision = $agent['decision'] ?? 'allow';
                return [
                    'passed' => ($decision === 'allow') && ($agent['ok'] ?? true),
                    'decision' => $decision,
                    'score' => $agent['score'] ?? 0,
                    'summary' => $agent['summary'] ?? '',
                    'matches' => $agent['matches'] ?? [],
                    'provider' => $agent['provider'] ?? 'dreamland_agent',
                    'languages' => $agent['languages'] ?? [],
                ];
            }
            $agent = Yii::$app->dreamlandModeration->moderateContent(['text' => (string) $text]);
            if (is_array($agent)) {
                return [
                    'passed' => ($agent['decision'] ?? 'allow') === 'allow',
                    'decision' => $agent['decision'] ?? 'allow',
                    'score' => $agent['score'] ?? 0,
                    'summary' => $agent['summary'] ?? '',
                    'matches' => $agent['matches'] ?? [],
                    'provider' => 'dreamland_agent',
                ];
            }
        }

        $normalized = mb_strtolower((string) $text, 'UTF-8');
        $keywords = LocalBlacklistKeyword::getActiveKeywords();
        foreach ($keywords as $keyword) {
            $pattern = '/\b' . preg_quote(mb_strtolower($keyword, 'UTF-8'), '/') . '\b/u';
            if (preg_match($pattern, $normalized)) {
                return ['passed' => false, 'reason' => 'local_blacklist', 'keyword' => $keyword];
            }
        }
        return ['passed' => true];
    }

    public function runOpenAiModeration($text)
    {
        $apiKey = getenv('OPENAI_API_KEY') ?: (Yii::$app->params['openAiApiKey'] ?? null);
        if (!$apiKey || trim((string) $text) === '') {
            return ['passed' => true, 'skipped' => true];
        }

        $ch = curl_init('https://api.openai.com/v1/moderations');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(['input' => $text]),
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string) $response, true);
        $flagged = (bool) ($data['results'][0]['flagged'] ?? false);
        return ['passed' => !$flagged, 'provider' => 'openai'];
    }

    /**
     * @param Post|\api\modules\v1\models\Post|int $post
     */
    public function finalizeScan($post, $passed, $reason = null, $decision = null)
    {
        $post = $this->normalizePost($post);

        if (!$passed || $decision === 'block') {
            $post->appraisal_status = 'rejected';
            $post->status = Post::STATUS_BLOCKED;
            $post->save(false);
            return 'rejected';
        }

        // Passed safety + AI moderation → human appraisal before PWA feed.
        $post->appraisal_status = 'pending_review';
        $post->status = Post::STATUS_BLOCKED;
        $post->save(false);

        return 'pending_review';
    }

    /**
     * Process queued safety jobs (PHP fallback when Node workers are offline).
     *
     * @return string[] result status per job
     */
    public function processQueuedJobs(int $limit = 10): array
    {
        $jobs = SafetyScanQueue::find()
            ->where(['status' => SafetyScanQueue::STATUS_QUEUED])
            ->orderBy(['id' => SORT_ASC])
            ->limit($limit)
            ->all();

        $results = [];
        foreach ($jobs as $job) {
            $results[] = $this->processJob($job);
        }

        return $results;
    }

    /**
     * Process the latest queued job for a post, or advance a stuck pending_safety post.
     */
    public function processPostScan(int $postId): ?string
    {
        $job = SafetyScanQueue::find()
            ->where(['video_id' => $postId, 'status' => SafetyScanQueue::STATUS_QUEUED])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if (!$job) {
            return null;
        }

        return $this->processJob($job);
    }

    public function processJob(SafetyScanQueue $job): string
    {
        $job->status = SafetyScanQueue::STATUS_PROCESSING;
        $job->save(false);

        try {
            $post = $this->normalizePost((int) $job->video_id);
            $text = $this->extractTextFromJob($job);

            // Step 1: Pre-configured moderation (Ghana lexicons + local blacklist).
            $scan = $this->runLocalTextScan($text);
            $passed = (bool) ($scan['passed'] ?? true);
            $decision = $scan['decision'] ?? ($passed ? 'allow' : 'block');

            // Step 2: AI multimodal moderation agent (when online).
            if ($passed && $decision !== 'block' && Yii::$app->has('dreamlandModeration')) {
                $mediaUrl = $job->media_url ?: $this->resolveMediaUrl($post);
                $agentResult = Yii::$app->dreamlandModeration->moderateContent([
                    'text' => $text,
                    'media_url' => $mediaUrl,
                ]);
                if (is_array($agentResult)) {
                    $decision = $agentResult['decision'] ?? $decision;
                    $agentOk = $agentResult['ok'] ?? true;
                    if ($decision === 'block' || !$agentOk) {
                        $passed = false;
                    } elseif ($decision === 'review') {
                        $passed = true;
                    } else {
                        $passed = ($decision === 'allow');
                    }
                } elseif (!Yii::$app->dreamlandModeration->isHealthy()) {
                    $decision = 'review';
                }
            }

            // Step 3: Appraisal workstation or rejection.
            $resultStatus = $this->finalizeScan($post, $passed, null, $decision);

            $job->status = SafetyScanQueue::STATUS_COMPLETED;
            $job->result_status = $resultStatus;
            $job->processed_at = date('Y-m-d H:i:s');
            $job->failure_reason = null;
            $job->save(false);

            return $resultStatus;
        } catch (\Throwable $e) {
            $job->status = SafetyScanQueue::STATUS_FAILED;
            $job->failure_reason = $e->getMessage();
            $job->processed_at = date('Y-m-d H:i:s');
            $job->save(false);
            Yii::error($e->getMessage(), __METHOD__);
            return 'failed';
        }
    }

    private function extractTextFromJob(SafetyScanQueue $job): string
    {
        if (!$job->text_payload) {
            return '';
        }

        $payload = json_decode($job->text_payload, true);
        if (!is_array($payload)) {
            return '';
        }

        $parts = [
            (string) ($payload['title'] ?? ''),
            (string) ($payload['description'] ?? ''),
        ];
        if (!empty($payload['tags']) && is_array($payload['tags'])) {
            $parts[] = implode(' ', $payload['tags']);
        }

        return trim(implode("\n", $parts));
    }

    /**
     * @param Post|\api\modules\v1\models\Post|int $post
     */
    private function normalizePost($post): Post
    {
        if ($post instanceof Post) {
            return $post;
        }
        if ($post instanceof \api\modules\v1\models\Post) {
            $common = Post::findOne((int) $post->id);
            if ($common) {
                return $common;
            }
            throw new \InvalidArgumentException('Post not found for safety scan.');
        }
        if (is_numeric($post)) {
            $common = Post::findOne((int) $post);
            if ($common) {
                return $common;
            }
            throw new \InvalidArgumentException('Post not found for safety scan.');
        }
        throw new \InvalidArgumentException('Invalid post model for safety scan.');
    }

    private function resolveMediaUrl(Post $post): ?string
    {
        return DreamlandMediaUrl::resolvePostVideoUrl($post);
    }
}
