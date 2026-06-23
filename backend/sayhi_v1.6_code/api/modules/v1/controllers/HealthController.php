<?php

namespace api\modules\v1\controllers;

use common\helpers\DreamlandWasabiStorage;
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
        try {
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
                    'statusCode' => 503,
                    'status' => 'error',
                    'message' => 'Database unavailable.',
                    'error' => $e->getMessage(),
                    'services' => $this->safeServiceMap(false, false, false, false, false, null),
                ];
            }

            $liveOk = false;
            $modOk = false;
            $uploadsWritable = false;
            $wasabi = ['ok' => false, 'message' => 'Wasabi check skipped.'];
            $aiOk = false;
            $health = null;
            $geminiOk = false;

            try {
                $liveOk = Yii::$app->has('dreamlandLive') ? Yii::$app->dreamlandLive->isHealthy() : false;
            } catch (\Throwable $e) {
                Yii::warning($e->getMessage(), __METHOD__);
            }
            try {
                $modOk = Yii::$app->has('dreamlandModeration') ? Yii::$app->dreamlandModeration->isHealthy() : false;
            } catch (\Throwable $e) {
                Yii::warning($e->getMessage(), __METHOD__);
            }
            try {
                $uploadsWritable = Yii::$app->has('fileUpload') ? Yii::$app->fileUpload->isLocalDiskWritable() : false;
            } catch (\Throwable $e) {
                Yii::warning($e->getMessage(), __METHOD__);
            }
            try {
                $wasabi = [
                    'ok' => DreamlandWasabiStorage::isConfigured(),
                    'message' => DreamlandWasabiStorage::isConfigured()
                        ? 'Wasabi configured (connection not probed on health check).'
                        : 'Wasabi is not configured.',
                ];
            } catch (\Throwable $e) {
                $wasabi = ['ok' => false, 'message' => $e->getMessage()];
            }
            try {
                $aiOk = Yii::$app->has('dreamlandAi') ? Yii::$app->dreamlandAi->isEnabled() : false;
            } catch (\Throwable $e) {
                Yii::warning($e->getMessage(), __METHOD__);
            }
            try {
                $health = $modOk && Yii::$app->has('dreamlandModeration')
                    ? Yii::$app->dreamlandModeration->getHealth()
                    : null;
                $geminiOk = is_array($health) && !empty($health['geminiConfigured']);
            } catch (\Throwable $e) {
                Yii::warning($e->getMessage(), __METHOD__);
            }

            return [
                'status' => 'ok',
                'message' => 'Dreamland API is healthy.',
                'checks' => [
                    'database' => $dbOk,
                    'uploads_writable' => $uploadsWritable,
                    'wasabi_storage' => $wasabi['ok'] ?? false,
                    'wasabi_message' => $wasabi['message'] ?? '',
                    'safety_queue_depth' => $queueDepth,
                    'live_server' => $liveOk,
                    'moderation_agent' => $modOk,
                    'ai_powered' => $aiOk,
                    'gemini_multimodal' => $geminiOk,
                    'dev_mode' => (bool) (Yii::$app->params['dreamlandDevMode'] ?? false),
                    'timestamp' => time(),
                ],
                'services' => $this->safeServiceMap(true, $liveOk, $modOk, $aiOk, $geminiOk, $health),
            ];
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return [
                'statusCode' => 500,
                'status' => 'error',
                'message' => 'Health check failed.',
                'error' => $e->getMessage(),
                'services' => $this->safeServiceMap(false, false, false, false, false, null),
            ];
        }
    }

    private function safeServiceMap(bool $apiOk, bool $liveOk, bool $modOk, bool $aiOk = false, bool $geminiOk = false, ?array $agentHealth = null): array
    {
        try {
            return $this->serviceMap($apiOk, $liveOk, $modOk, $aiOk, $geminiOk, $agentHealth);
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            return [
                'api_ok' => $apiOk,
                'live_ok' => $liveOk,
                'moderation_ok' => $modOk,
                'uploads' => null,
                'service_map_error' => $e->getMessage(),
            ];
        }
    }

    private function serviceMap(bool $apiOk, bool $liveOk, bool $modOk, bool $aiOk = false, bool $geminiOk = false, ?array $agentHealth = null): array
    {
        $params = Yii::$app->params;
        $apiBase = rtrim((string) ($params['siteUrl'] ?? 'http://localhost:8080'), '/');
        $pwaUrl = rtrim((string) ($params['dreamlandPwaUrl'] ?? 'http://localhost:3000'), '/');
        $adminUrl = rtrim((string) ($params['dreamlandAdminUrl'] ?? 'http://localhost:8081'), '/');
        $geminiModel = is_array($agentHealth) ? ($agentHealth['gemini']['model'] ?? null) : null;

        return [
            'pwa' => $pwaUrl,
            'api' => $apiBase . '/v1',
            'admin' => $adminUrl,
            'uploads' => $this->safeUploadsBase(),
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

    private function safeUploadsBase(): string
    {
        try {
            return DreamlandWasabiStorage::uploadsBaseForApi();
        } catch (\Throwable $e) {
            return \common\helpers\DreamlandMediaUrl::publicUploadsBase();
        }
    }
}
