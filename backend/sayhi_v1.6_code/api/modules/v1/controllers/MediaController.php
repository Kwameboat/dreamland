<?php

namespace api\modules\v1\controllers;

use common\helpers\DreamlandMediaUrl;
use Yii;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

/**
 * Serves uploaded reel media from local disk (cPanel trial mode).
 */
class MediaController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        return $behaviors;
    }

    /** GET /v1/media/reel?name={filename} */
    public function actionReel()
    {
        $filename = basename((string) Yii::$app->request->get('name', ''));
        if ($filename === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
            throw new NotFoundHttpException('Invalid file name.');
        }

        foreach (DreamlandMediaUrl::localUploadDirs() as $dir) {
            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            if (!is_file($path)) {
                continue;
            }

            $mime = mime_content_type($path) ?: 'application/octet-stream';
            $response = Yii::$app->response;
            $response->format = \yii\web\Response::FORMAT_RAW;
            $response->headers->set('Content-Type', $mime);
            $response->headers->set('Content-Length', (string) filesize($path));
            $response->headers->set('Accept-Ranges', 'bytes');
            $response->headers->set('Cache-Control', 'public, max-age=86400');
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->content = file_get_contents($path);

            return $response;
        }

        throw new NotFoundHttpException('Video file not found on server.');
    }
}
