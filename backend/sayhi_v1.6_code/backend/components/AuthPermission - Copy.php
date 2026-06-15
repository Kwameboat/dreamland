<?php
namespace backend\components;
use backend\controllers\AdministratorController;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use backend\models\ModuleAuthUser;
//define( 'API_ACCESS_KEY', Yii::$app->params['apiKey.firebaseCloudMessaging']);

class AuthPermission extends Component{
  
  const ADMINISTRATOR = 'administrator';
  const POST = 'post';
  const USER = 'user';
  const COMPETITION='competition';
  const CLUB ='club';
  const SUPPORT_REQUEST = 'supportRequest';

  const PAYMENT = "payment";
  const PACKAGE ="package";

  const TV_CHANNEL ="tvChannel";
  const PODCAST ="podcast";
  const GIFT ="gift";
  const FAQ ="faq";
  const ORGANIZATION ="organization";
  const EVENT ="event";
  const FUND_RAISING ="fundRaising";
  const REEL ="reel";
  const POLL ="poll";
  const BROADCAST_NOTIFICATIONS ="broadcastNotifications";
  const COUPON ="coupon";
  const DATING ="dating";
  const STORY ="story";
  const JOB ="job";
  const AD ="ad";
  const REPORT ="report";
  const LIVE_HISTORY ="liveHistory";
  const PROMOTION ="promotion";
  
  const SETTING ="setting";
  
  public function can($moduleName)
	{
		
		
    //echo Yii::$app->controller->id;
    echo Yii::$app->controller->action->id;


		$modelModuleAuthUser = new ModuleAuthUser();

		/*$result = $modelModuleAuthUser->find()->where(['user_id' => 1, 'module_auth_id'=>1])
		->one();*/

    $uid =Yii::$app->user->identity->id;
		$result = $modelModuleAuthUser->find()->where(['user_id' =>  $uid])
		->joinWith('moduleAuth')
		->andWhere(['module_auth.alias' => $moduleName])
		->one();
	
		if($result){
			
			if($result->is_enabled){
				return true;
			}else{
				return false;
			}
		}else{
			return true;
		}
		
	}

 
}
?>