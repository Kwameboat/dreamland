<?php

namespace api\modules\v1\controllers;

use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;

class DreamlandAiController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'optional' => ['status', 'check-text', 'rank-feed'],
            'authMethods' => [HttpBearerAuth::className()],
        ];
        return $behaviors;
    }

    /** GET /v1/dreamland-ai/status */
    public function actionStatus()
    {
        if (!Yii::$app->has('dreamlandAi')) {
            return ['enabled' => false, 'provider' => 'dreamland-ai'];
        }
        return Yii::$app->dreamlandAi->getStatus();
    }

    /** POST /v1/dreamland-ai/check-text — signup / profile safety */
    public function actionCheckText()
    {
        $params = Yii::$app->request->bodyParams;
        $name = trim((string) ($params['name'] ?? ''));
        $username = trim((string) ($params['username'] ?? ''));
        $text = trim((string) ($params['text'] ?? ''));
        if ($text !== '') {
            $name = $text;
            $username = '';
        }
        if (!Yii::$app->has('dreamlandAi')) {
            return ['ok' => true, 'decision' => 'allow', 'summary' => 'AI offline — allowed in dev'];
        }
        $result = Yii::$app->dreamlandAi->checkSignupText($name, $username);
        return $result;
    }

    /** POST /v1/dreamland-ai/caption-suggest — creator caption assist */
    public function actionCaptionSuggest()
    {
        if (!Yii::$app->user->identity) {
            return ['statusCode' => 401, 'message' => 'Sign in as a creator.'];
        }
        $params = Yii::$app->request->bodyParams;
        if (!Yii::$app->has('dreamlandAi')) {
            return ['statusCode' => 503, 'message' => 'Dreamland AI is offline.'];
        }
        $suggestion = Yii::$app->dreamlandAi->suggestCaptions([
            'title' => (string) ($params['title'] ?? ''),
            'description' => (string) ($params['description'] ?? ''),
            'genre' => (string) ($params['genre'] ?? ''),
        ]);
        if (!$suggestion) {
            return ['statusCode' => 503, 'message' => 'Could not generate caption suggestions.'];
        }
        return $suggestion;
    }

    /** POST /v1/dreamland-ai/rank-feed */
    public function actionRankFeed()
    {
        if (!Yii::$app->has('dreamlandAi')) {
            return ['statusCode' => 503, 'message' => 'Dreamland AI is offline.', 'posts' => []];
        }

        $params = Yii::$app->request->bodyParams;
        $posts = is_array($params['posts'] ?? null) ? $params['posts'] : [];
        $preferences = is_array($params['preferences'] ?? null) ? $params['preferences'] : [];

        if (!$posts) {
            return ['message' => 'ok', 'posts' => []];
        }

        $ranked = Yii::$app->dreamlandAi->rankFeedPosts($posts, $preferences);

        return [
            'message' => 'ok',
            'posts' => $ranked,
            'provider' => Yii::$app->dreamlandAi->getStatus()['provider'] ?? 'dreamland-ai',
        ];
    }
}
