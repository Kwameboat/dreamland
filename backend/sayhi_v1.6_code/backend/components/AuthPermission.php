<?php
namespace backend\components;
use backend\controllers\AdministratorController;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use backend\models\ModuleAuthUser;
use backend\models\ModuleAuth;
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
		
		
    $controllerId = Yii::$app->controller->id;
    $actionId =  Yii::$app->controller->action->id;


		$modelModuleAuthUser = new ModuleAuthUser();
    $modelModuleAuth = new ModuleAuth();
    $urlAction = $controllerId.'/'.$actionId;

    /*$uid =Yii::$app->user->identity->id;
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
		}*/
    $identity = Yii::$app->user->identity;
    if (!$identity) {
      return false;
    }
    $uid = $identity->id;
    $resultModule = $modelModuleAuth->find()->Where(['module_auth.alias' => $moduleName,'module_auth.level' => 1])->one();
    $moduleActionId= 0;
    
    //echo '<pre>';
    //print_r($resultModule);
    if($resultModule){
      //print_r($resultModule->moduleAuthChild);
      foreach($resultModule->moduleAuthChild as $childAction){
        $actionListArr = explode(',',$childAction->action_list);
        
        $found_key = array_search($urlAction,$actionListArr);
        if(is_int($found_key)){
          $moduleActionId = $childAction->id;
          break;
        }
      
      }
      if($moduleActionId==0){
        $moduleActionId=$resultModule->id;
      }
      
      if($moduleActionId>0){
          $resultPermission = $modelModuleAuthUser->find()->where(['user_id' =>  $uid,'module_auth_id'=>$moduleActionId])->one();
          
          if($resultPermission){
			
            if($resultPermission->is_enabled){
              return true;
            }else{
              return false;
            }

          }else{
            return true;
          } 
        }else{




          return true;
        }

    }
  }
}
?>