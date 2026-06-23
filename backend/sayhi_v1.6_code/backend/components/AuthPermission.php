<?php
namespace backend\components;

use backend\models\ModuleAuth;
use backend\models\ModuleAuthUser;
use common\models\User;
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
    const DREAMLAND_APPRAISAL = 'dreamlandAppraisal';
    const DREAMLAND_MODERATION = 'dreamlandModeration';
    const DREAMLAND_SAFETY = 'dreamlandSafety';
    const DREAMLAND_SETTINGS = 'dreamlandSettings';
    const CREDIT_PACKAGE = 'creditPackage';

    /** @var array<string, bool> */
    private $_canResults = [];

    /** @var bool */
    private $_contextLoaded = false;

    /** @var array<string, ModuleAuth> */
    private $_modulesByAlias = [];

    /** @var array<int, ModuleAuthUser> */
    private $_userPermissions = [];

    public function isSuperAdmin(): bool
    {
        $identity = Yii::$app->user->identity;
        return $identity && (int) $identity->role === User::ROLE_ADMIN;
    }

    /**
     * @param string[] $moduleNames
     */
    public function canAny(array $moduleNames): bool
    {
        foreach ($moduleNames as $moduleName) {
            if ($this->can($moduleName)) {
                return true;
            }
        }

        return false;
    }

    public function can($moduleName)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $identity = Yii::$app->user->identity;
        if (!$identity) {
            return false;
        }

        $controller = Yii::$app->controller;
        $urlAction = $controller->id . '/' . $controller->action->id;
        $cacheKey = (int) $identity->id . ':' . $moduleName . ':' . $urlAction;

        if (array_key_exists($cacheKey, $this->_canResults)) {
            return $this->_canResults[$cacheKey];
        }

        $this->_canResults[$cacheKey] = $this->evaluateCan($moduleName, $urlAction, (int) $identity->id);

        return $this->_canResults[$cacheKey];
    }

    private function ensurePermissionContext(): void
    {
        if ($this->_contextLoaded) {
            return;
        }

        $this->_contextLoaded = true;
        $identity = Yii::$app->user->identity;
        if (!$identity) {
            return;
        }

        try {
            $this->_userPermissions = ModuleAuthUser::find()
                ->where(['user_id' => (int) $identity->id])
                ->indexBy('module_auth_id')
                ->all();

            $modules = ModuleAuth::find()
                ->where(['level' => 1])
                ->with('moduleAuthChild')
                ->all();

            foreach ($modules as $module) {
                $this->_modulesByAlias[$module->alias] = $module;
            }
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
        }
    }

    private function evaluateCan(string $moduleName, string $urlAction, int $uid): bool
    {
        try {
            $this->ensurePermissionContext();

            $resultModule = $this->_modulesByAlias[$moduleName] ?? null;
            if (!$resultModule) {
                return false;
            }

            $moduleActionId = 0;
            foreach ($resultModule->moduleAuthChild as $childAction) {
                $actionListArr = array_filter(array_map('trim', explode(',', (string) $childAction->action_list)));
                $foundKey = array_search($urlAction, $actionListArr, true);
                if (is_int($foundKey)) {
                    $moduleActionId = (int) $childAction->id;
                    break;
                }
            }

            if ($moduleActionId === 0) {
                $moduleActionId = (int) $resultModule->id;
            }

            if ($moduleActionId <= 0) {
                return false;
            }

            $resultPermission = $this->_userPermissions[$moduleActionId] ?? null;
            if ($resultPermission) {
                return (bool) $resultPermission->is_enabled;
            }

            return false;
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            return false;
        }
    }
}
