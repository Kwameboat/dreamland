<?php

namespace api\modules\v1\controllers;

use Yii;
use yii\rest\Controller;

class HealthController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        return $behaviors;
    }

    /**
     * GET /v1/health
     */
    public function actionIndex()
    {
        $dbOk = false;
        $queueDepth = null;
        try {
            Yii::$app->db->createCommand('SELECT 1')->queryScalar();
            $dbOk = true;
            if (Yii::$app->db->schema->getTableSchema('safety_scan_queue', true)) {
                $queueDepth = (int) Yii::$app->db->createCommand(
                    "SELECT COUNT(*) FROM safety_scan_queue WHERE status='queued'"
                )->queryScalar();
            }
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Database unavailable.',
                'error' => $e->getMessage(),
                'services' => $this->serviceMap(false, false, false, false, false, null),
            ];
        }

        $liveOk = Yii::$app->has('dreamlandLive') ? Yii::$app->dreamlandLive->isHealthy() : false;
        $modOk = Yii::$app->has('dreamlandModeration') ? Yii::$app->dreamlandModeration->isHealthy() : false;
        $aiOk = Yii::$app->has('dreamlandAi') ? Yii::$app->dreamlandAi->isEnabled() : false;
        $health = $modOk && Yii::$app->has('dreamlandModeration')
            ? Yii::$app->dreamlandModeration->getHealth()
            : null;
        $geminiOk = is_array($health) && !empty($health['geminiConfigured']);

        return [
            'status' => 'ok',
            'message' => 'Dreamland API is healthy.',
            'checks' => [
                'database' => $dbOk,
                'safety_queue_depth' => $queueDepth,
                'live_server' => $liveOk,
                'moderation_agent' => $modOk,
                'ai_powered' => $aiOk,
                'gemini_multimodal' => $geminiOk,
                'dev_mode' => (bool) (Yii::$app->params['dreamlandDevMode'] ?? false),
                'timestamp' => time(),
            ],
            'services' => $this->serviceMap(true, $liveOk, $modOk, $aiOk, $geminiOk, $health),
        ];
    }

    private function serviceMap(bool $apiOk, bool $liveOk, bool $modOk, bool $aiOk = false, bool $geminiOk = false, ?array $agentHealth = null): array
    {
        $params = Yii::$app->params;
        $apiBase = rtrim((string) ($params['siteUrl'] ?? 'http://localhost:8080'), '/');
        $geminiModel = is_array($agentHealth) ? ($agentHealth['gemini']['model'] ?? null) : null;

        return [
            'pwa' => 'http://localhost:3000',
            'api' => $apiBase . '/v1',
            'admin' => 'http://localhost:8081',
            'uploads' => $apiBase . '/frontend/web/uploads/image',
            'live_signaling' => (string) ($params['dreamlandLiveSignalingUrl'] ?? 'http://localhost:4443'),
            'live_ok' => $liveOk,
            'moderation_agent' => (string) ($params['dreamlandModerationAgentUrl'] ?? 'http://localhost:4444'),
            'moderation_ok' => $modOk,
            'ai_ok' => $aiOk,
            'gemini_ok' => $geminiOk,
            'gemini_model' => $geminiModel ?: (string) ($params['dreamlandGeminiModel'] ?? 'gemini-2.0-flash'),
            'api_ok' => $apiOk,
        ];
    }
}
