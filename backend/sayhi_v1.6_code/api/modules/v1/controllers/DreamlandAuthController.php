<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Package;
use api\modules\v1\models\User;
use Yii;
use yii\rest\ActiveController;

class DreamlandAuthController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\User';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);
        return $actions;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        return $behaviors;
    }

    /**
     * POST /v1/dreamland-auth/register
     * Body: username, email, password, account_type (viewer|creator)
     */
    public function actionRegister()
    {
        $params = Yii::$app->request->bodyParams;
        $accountType = strtolower(trim((string) ($params['account_type'] ?? 'viewer')));
        if (!in_array($accountType, ['viewer', 'creator'], true)) {
            return ['statusCode' => 422, 'message' => 'account_type must be viewer or creator.'];
        }

        $model = new User();
        $model->scenario = 'register';
        $model->username = trim((string) ($params['username'] ?? ''));
        $model->email = strtolower(trim((string) ($params['email'] ?? '')));
        $model->password = (string) ($params['password'] ?? '');
        $model->name = trim((string) ($params['name'] ?? $model->username));
        $model->device_type = (string) ($params['device_type'] ?? '3');
        $model->device_token = (string) ($params['device_token'] ?? '');
        $model->role = $accountType === 'creator' ? User::ROLE_AGENT : User::ROLE_CUSTOMER;
        $model->status = User::STATUS_ACTIVE;
        $model->is_email_verified = User::IS_EMAIL_VERIFIED_YES;
        $model->is_phone_verified = 0;
        $model->account_created_with = 1;
        $model->profile_visibility = 1;
        $model->is_push_notification_allow = 0;

        if ($model->hasAttribute('dreamland_account_type')) {
            $model->dreamland_account_type = $accountType;
        }
        if ($model->hasAttribute('dreamland_creator_status')) {
            $model->dreamland_creator_status = $accountType === 'creator' ? 'pending' : 'none';
        }

        if (Yii::$app->has('dreamlandAi')) {
            $aiCheck = Yii::$app->dreamlandAi->checkSignupText($model->name, $model->username);
            if (empty($aiCheck['ok'])) {
                return [
                    'statusCode' => 422,
                    'message' => $aiCheck['message'] ?? 'Dreamland AI rejected this profile text.',
                    'ai' => [
                        'decision' => $aiCheck['decision'] ?? 'block',
                        'score' => $aiCheck['score'] ?? null,
                    ],
                ];
            }
        }

        if (!$model->validate()) {
            $existing = User::find()
                ->where(['email' => $model->email])
                ->andWhere(['<>', 'status', User::STATUS_DELETED])
                ->one();
            if ($existing && $existing->validatePassword($model->password, $existing->password_hash)) {
                return $this->registrationLoginResponse($existing, $accountType, 'You already have an account — signed you in.');
            }

            $flat = [];
            foreach ($model->errors as $fieldErrors) {
                foreach ((array) $fieldErrors as $msg) {
                    $flat[] = $msg;
                }
            }
            $message = $flat[0] ?? 'Validation failed.';
            if (isset($model->errors['email']) && $existing) {
                $message = 'This email is already registered. Sign in instead.';
            }
            return ['statusCode' => 422, 'message' => $message, 'errors' => $model->errors];
        }

        $defaultPackage = (new Package())->getDefaultPackage();
        if ($defaultPackage) {
            $model->available_coin = $accountType === 'creator' ? 0 : (int) $defaultPackage->coin;
        }

        $referralUserId = (int) ($params['referral_user_id'] ?? 0);
        if ($referralUserId > 0 && $accountType === 'viewer') {
            $model->available_coin = (int) $model->available_coin + 5;
        }

        if ($model->save()) {
            if ($this->hasAccountTypeColumn() || $this->hasCreatorStatusColumn()) {
                $userUpdates = [];
                if ($this->hasAccountTypeColumn()) {
                    $userUpdates['dreamland_account_type'] = $accountType;
                }
                if ($this->hasCreatorStatusColumn()) {
                    $userUpdates['dreamland_creator_status'] = $accountType === 'creator' ? 'pending' : 'none';
                }
                if ($userUpdates !== []) {
                    Yii::$app->db->createCommand()
                        ->update('user', $userUpdates, ['id' => $model->id])
                        ->execute();
                }
            }

            if ($referralUserId > 0 && $referralUserId !== (int) $model->id) {
                $referrer = User::findOne(['id' => $referralUserId, 'status' => User::STATUS_ACTIVE]);
                if ($referrer) {
                    $referrer->available_coin = (int) $referrer->available_coin + 5;
                    $referrer->save(false);
                }
            }

            return $this->registrationLoginResponse($model, $accountType, $accountType === 'creator'
                ? 'Creator account created. Upload unlocks after Dreamland approves your application.'
                : 'Viewer account created. Welcome to Dreamland.');
        }

        return ['statusCode' => 422, 'message' => 'Registration failed. Please try again.'];
    }

    private function registrationLoginResponse(User $model, string $accountType, string $message): array
    {
        try {
            $profile = $model->getProfile($model->id) ?: $model;
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            $profile = $model;
        }

        if ($profile && $this->hasAccountTypeColumn()) {
            $profile->dreamland_account_type = $profile->dreamland_account_type ?? $accountType;
        }
        if ($profile && $this->hasCreatorStatusColumn()) {
            $profile->dreamland_creator_status = $profile->dreamland_creator_status
                ?? ($accountType === 'creator' ? 'pending' : 'none');
        }

        return [
            'message' => $message,
            'user' => $this->decorateUser($profile, $accountType),
            'auth_key' => $profile->auth_key ?? $model->auth_key,
        ];
    }

    private function hasAccountTypeColumn(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $schema = Yii::$app->db->schema->getTableSchema('user', true);
        $cached = $schema && isset($schema->columns['dreamland_account_type']);
        return $cached;
    }

    private function hasCreatorStatusColumn(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $schema = Yii::$app->db->schema->getTableSchema('user', true);
        $cached = $schema && isset($schema->columns['dreamland_creator_status']);
        return $cached;
    }

    private function decorateUser($user, string $accountType)
    {
        if (!$user) {
            return null;
        }
        $data = $user->toArray();
        $data['dreamland_account_type'] = $this->hasAccountTypeColumn()
            ? ($user->dreamland_account_type ?? $accountType)
            : $accountType;
        if ($this->hasCreatorStatusColumn()) {
            $data['dreamland_creator_status'] = $user->dreamland_creator_status
                ?? ($accountType === 'creator' ? 'pending' : 'none');
        }
        return $data;
    }

    /**
     * POST /v1/dreamland-auth/forgot-password
     * Body: email
     */
    public function actionForgotPassword()
    {
        $email = trim((string) (Yii::$app->request->bodyParams['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['statusCode' => 422, 'message' => 'Enter a valid email address.'];
        }

        $user = User::find()->where(['email' => $email, 'status' => User::STATUS_ACTIVE])->one();
        if (!$user) {
            return ['statusCode' => 422, 'message' => 'No active Dreamland account uses this email.'];
        }

        $otp = (string) mt_rand(100000, 999999);
        $token = md5(time() . rand(10, 100)) . '_' . (time() + 900);
        $user->password_reset_token = $token;
        $user->verification_token = $otp;
        $user->verification_with = User::VERIFICATION_WITH_EMAIL;

        if (!$user->save(false)) {
            return ['statusCode' => 422, 'message' => 'Could not start password reset. Try again.'];
        }

        $mailSent = false;
        try {
            $fromMail = Yii::$app->params['senderEmail'] ?? 'noreply@dreamland.app';
            $fromName = Yii::$app->params['senderName'] ?? 'Dreamland';
            $mailSent = Yii::$app->mailer->compose('password_forgot', [
                'username' => $user->username,
                'otp' => $otp,
            ])
                ->setSubject('Dreamland password reset code')
                ->setFrom([$fromMail => $fromName])
                ->setTo($user->email)
                ->send();
        } catch (\Throwable $e) {
            $mailSent = false;
        }

        $response = [
            'message' => $mailSent
                ? 'We sent a reset code to your email.'
                : 'Reset code generated. Check your email or use the dev code below.',
            'token' => $token,
        ];

        $devMode = !empty(Yii::$app->params['dreamlandDevMode'])
            || (int) (Yii::$app->params['siteMode'] ?? 1) !== 1;
        if ($devMode || !$mailSent) {
            $response['otp'] = $otp;
        }

        return $response;
    }

    /**
     * POST /v1/dreamland-auth/verify-reset-otp
     * Body: token, otp
     */
    public function actionVerifyResetOtp()
    {
        $params = Yii::$app->request->bodyParams;
        $token = trim((string) ($params['token'] ?? ''));
        $otp = trim((string) ($params['otp'] ?? ''));

        if ($token === '' || $otp === '') {
            return ['statusCode' => 422, 'message' => 'Reset token and verification code are required.'];
        }

        $expiry = (int) (@explode('_', $token)[1] ?? 0);
        if ($expiry > 0 && time() > $expiry) {
            return ['statusCode' => 422, 'message' => 'This reset link expired. Request a new code.'];
        }

        $user = User::find()->where([
            'password_reset_token' => $token,
            'verification_token' => $otp,
            'status' => User::STATUS_ACTIVE,
        ])->one();

        if (!$user) {
            return ['statusCode' => 422, 'message' => 'Invalid or expired verification code.'];
        }

        $newToken = md5(time() . rand(10, 100)) . '_' . (time() + 900);
        $user->password_reset_token = $newToken;
        $user->verification_token = null;

        if (!$user->save(false)) {
            return ['statusCode' => 422, 'message' => 'Could not verify code. Try again.'];
        }

        return [
            'message' => 'Code verified. Choose your new password.',
            'token' => $newToken,
        ];
    }

    /**
     * POST /v1/dreamland-auth/reset-password
     * Body: token, password
     */
    public function actionResetPassword()
    {
        $params = Yii::$app->request->bodyParams;
        $token = trim((string) ($params['token'] ?? ''));
        $password = (string) ($params['password'] ?? '');

        if ($token === '') {
            return ['statusCode' => 422, 'message' => 'Reset token is missing. Start again.'];
        }
        if (strlen($password) < 6) {
            return ['statusCode' => 422, 'message' => 'Password must be at least 6 characters.'];
        }

        $expiry = (int) (@explode('_', $token)[1] ?? 0);
        if ($expiry > 0 && time() > $expiry) {
            return ['statusCode' => 422, 'message' => 'This reset session expired. Request a new code.'];
        }

        $user = User::find()->where([
            'password_reset_token' => $token,
            'status' => User::STATUS_ACTIVE,
        ])->one();

        if (!$user) {
            return ['statusCode' => 422, 'message' => 'Reset session invalid. Request a new code.'];
        }

        $user->password_hash = Yii::$app->security->generatePasswordHash($password);
        $user->auth_key = null;
        $user->password_reset_token = null;
        $user->verification_token = null;
        $user->verification_with = null;
        $user->is_email_verified = User::IS_EMAIL_VERIFIED_YES;

        if (!$user->save(false)) {
            return ['statusCode' => 422, 'message' => 'Could not update password. Try again.'];
        }

        return ['message' => 'Password updated. You can sign in with your new password.'];
    }
}
