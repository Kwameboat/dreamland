<?php

namespace backend\models;

use app\models\User;
use Yii;

/**
 * Form model for admin content-creator CRUD.
 */
class CreatorForm extends User
{
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['create'] = ['username', 'email', 'password', 'confirmPassword', 'name', 'status', 'is_verified', 'imageFile'];
        $scenarios['update'] = ['username', 'email', 'name', 'status', 'is_verified', 'imageFile'];
        return $scenarios;
    }

    public function rules()
    {
        return array_merge(parent::rules(), [
            [['name'], 'required', 'on' => ['create', 'update']],
            [['name'], 'string', 'max' => 255],
        ]);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name' => 'Display name',
            'is_verified' => 'Verified creator',
        ]);
    }

    public function applyCreatorIdentity(): void
    {
        $this->role = self::ROLE_AGENT;
        if ($this->hasAttribute('dreamland_account_type')) {
            $this->dreamland_account_type = 'creator';
        }
    }

    public function prepareForInsert(): void
    {
        $this->applyCreatorIdentity();
        if (!$this->auth_key) {
            $this->auth_key = Yii::$app->security->generateRandomString();
        }
        if (!$this->status) {
            $this->status = self::STATUS_ACTIVE;
        }
    }
}
