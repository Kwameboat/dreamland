<?php 
use yii\helpers\Url;
use common\models\Setting;

$modelSetting           = new Setting();
$setting          = $modelSetting->getSettingData();
                        
?>
<section class="app_store" >
  <div class="container">
    <!-- Row  -->
    <div class="row justify-content-center">
      <div class="col-md-10 text-center">
        <h2 class="title">Download on App Store</h2>
        <div class="clearfix"></div>
      </div>
    </div>
    <!-- Row  -->
    <div class="row">
      <div class="app_parts">
        <a target="_blank" href="<?=$setting->google_play_store_url?>"><button class="app-btn btn" type="submit" value="butten"><i class="fa fa-android app-icon"></i>Google Play Store</button></a>  
        <a target="_blank" href="<?=$setting->apple_app_store_url?>"><button class="app-btn btn" type="submit" value="butten"><i class="fa fa-apple app-icon "></i> Apple App Store</button></a>
      </div>
    </div>
  </div>
</section>