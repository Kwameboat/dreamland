<?php
namespace backend\components;

use backend\models\ModuleAuth;
use backend\models\ModuleAuthUser;
use Yii;
use yii\base\Component;

class AuthPermission extends Component
{
    const ADMINISTRATOR = 'administrator';
    const POST = 'post';
    const USER = 'user';
    const COMPETITION = 'competition';
    const CLUB = 'club';
    const SUPPORT_REQUEST = 'supportRequest';
    const PAYMENT = 'payment';
    const PACKAGE = 'package';
    const TV_CHANNEL = 'tvChannel';
    const PODCAST = 'podcast';
    const GIFT = 'gift';
    const FAQ = 'faq';
    const ORGANIZATION = 'organization';
    const EVENT = 'event';
    const FUND_RAISING = 'fundRaising';
    const REEL = 'reel';
    const POLL = 'poll';
    const BROADCAST_NOTIFICATIONS = 'broadcastNotifications';
    const COUPON = 'coupon';
    const DATING = 'dating';
    const STORY = 'story';
    const JOB = 'job';
    const AD = 'ad';
    const REPORT = 'report';
    const LIVE_HISTORY = 'liveHistory';
    const PROMOTION = 'promotion';
    const SETTING = 'setting';

    public function can($moduleName)
    {
        $identity = Yii::$app->user->identity;
        if (!$identity) {
            return false;
        }

        try {
            $controllerId = Yii::$app->controller->id;
            $actionId = Yii::$app->controller->action->id;
            $modelModuleAuthUser = new ModuleAuthUser();
            $modelModuleAuth = new ModuleAuth();
            $urlAction = $controllerId . '/' . $actionId;
            $uid = $identity->id;
            $resultModule = $modelModuleAuth->find()
                ->where(['module_auth.alias' => $moduleName, 'module_auth.level' => 1])
                ->one();

            if (!$resultModule) {
                return true;
            }

            $moduleActionId = 0;
            foreach ($resultModule->moduleAuthChild as $childAction) {
                $actionListArr = explode(',', (string) $childAction->action_list);
                $found_key = array_search($urlAction, $actionListArr, true);
                if (is_int($found_key)) {
                    $moduleActionId = $childAction->id;
                    break;
                }
            }
            if ($moduleActionId === 0) {
                $moduleActionId = $resultModule->id;
            }

            if ($moduleActionId > 0) {
                $resultPermission = $modelModuleAuthUser->find()
                    ->where(['user_id' => $uid, 'module_auth_id' => $moduleActionId])
                    ->one();

                if ($resultPermission) {
                    return (bool) $resultPermission->is_enabled;
                }

                return true;
            }

            return true;
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            return true;
        }
    }
}
