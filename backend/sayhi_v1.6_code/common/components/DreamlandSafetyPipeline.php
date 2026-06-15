<?php

namespace common\components;

use common\models\LocalBlacklistKeyword;
use common\models\Post;
use common\models\SafetyScanQueue;
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
        if ($decision === 'review' || (!$passed && $decision !== 'block')) {
            if ((int) $post->is_paid === 1 || $decision === 'review') {
                $post->appraisal_status = 'pending_review';
                $post->status = 9;
                $post->save(false);
                return 'pending_review';
            }
        }

        if (!$passed || $decision === 'block') {
            $post->appraisal_status = 'rejected';
            $post->status = 9;
            $post->save(false);
            return 'rejected';
        }

        if ((int) $post->is_paid === 1) {
            $post->appraisal_status = 'pending_review';
        } else {
            $post->appraisal_status = 'active';
            $post->status = 10;
        }
        $post->save(false);
        return $post->appraisal_status;
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

    private function resolveMediaUrl(Post $post)
    {
        $gallery = \api\modules\v1\models\PostGallary::find()
            ->where(['post_id' => $post->id, 'status' => 10])
            ->orderBy(['is_default' => SORT_DESC, 'id' => SORT_ASC])
            ->one();
        if (!$gallery) {
            return null;
        }
        $folder = Yii::$app->params['pathUploadImageFolder'] ?? 'image';

        return Yii::$app->params['siteUrl'] . Yii::$app->urlManagerFrontend->baseUrl . '/uploads/' . $folder . '/' . $gallery->filename;
    }
}
