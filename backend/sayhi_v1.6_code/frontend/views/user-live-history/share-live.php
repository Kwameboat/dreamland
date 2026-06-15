<?php
use yii\helpers\Html;
use yii\helpers\Url;

//print_r($postResult);

?>
 

<div class="header" style="background-color: #6296c4;">
  <div class="container po-relative">
    <nav class="navbar navbar-expand-lg hover-dropdown header-nav-bar"> <a href="<?=Url::home(true)?>"
        class="navbar-brand"><b><?=Yii::$app->name;?></b></a>

      <div class="collapse navbar-collapse" id="h5-info">


      </div>
    </nav>
  </div>
</div>

<style>
  .app_store .app_parts {
    text-align: center;
    margin: 10px auto 30px;
  }

  .counter_icon {
    width: 40px;
    text-align: left;
    padding-bottom: 20px;
    ;
  }

  .app_store .title {
    margin: 5px 0 10px;
    font-size: 30px;
    color: #fff;
    position: relative;
    padding: 0 0 20px 0;
  }

  .media_comment {
    margin-bottom: :10px;
  }

  .media-object {
    border-radius: 50%;
    margin-right: 5px;
    
    
  }

  .title_user {
    font-size: 14px;
    font-weight: bold;
  }

  .komen {
    font-size: 14px;
  }

  .geser {
    margin-left: 40px;
    margin-top: 5px;
  }

  .comment-date {
    font-size: 10px;
    color: #9C9998;
  }

  .comment_container {
    max-height: 450px;
    overflow: auto;
  }

  .app_store:after {

    background: #6296c4;
  }
</style>



<!-- End Featured_ads -->



<!-- We_Bes -->

<section class="we_bes p-b-45">
  <div class="container">



    <div class="row">
      <div class="col-xl-12">
    
        <div class="d-flex justify-content-center m-t-40" >
        <h5> Live video is not supported on web. Please download application to view.
          
          </h5>
        </div><br>
      
      </div>

     

    </div>
  </div>
</section>

<!-- End We_Bes -->
<!-- App_Store -->
<section class="app_store" style="height:230px">
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
<!-- End App_Store -->