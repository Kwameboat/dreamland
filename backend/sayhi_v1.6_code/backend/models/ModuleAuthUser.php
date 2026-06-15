<?php
namespace backend\models;
use Yii;
use backend\models\ModuleAuth;
class ModuleAuthUser extends \yii\db\ActiveRecord
{
    
   
   
    public $module_ids;

    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'module_auth_user';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','module_auth_id','is_enabled'], 'integer'],
            [['module_ids'], 'save'],
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
         
        ];
    }
    

    public function getModuleAuth()
    {
        return $this->hasOne(ModuleAuth::className(), ['id'=>'module_auth_id']);
        
    }

    

    
}
