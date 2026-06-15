<?php
use yii\helpers\Html;
use yii\helpers\Url;


?>



<!-- App_Store -->
<section class="app_store">
  <div class="container"> 
    <!-- Row  -->
    <div class="row justify-content-center">
      <div class="col-md-10 text-center">
        <h2 class="title">Welcome to Dashbord</h2>
        <div class="clearfix"></div>
        <h6 class="subtitle">Here you can manage your account, And also here your can delete your aacount and logout the account.</h6>
      </div>
    </div>
    <!-- Row  -->
    <div class="row">
      <div class="app_parts ">
     <?php if(@Yii::$app->user->identity->id){ ?>
        <?php 
        // echo Html::a(
        //       'Redeem Coupon',
        //       ['/site/redeem-coupon'],
        //       ['data-method' => 'post', 'class' => 'app-btn btn']
        //   ) 
          ?> 
          <?php
          $userId = @Yii::$app->user->identity->id;
          ?>
          <?= Html::a(
                'Delete Account',
                [
                    'delete-account',
                    'id' => $userId,
                    
                ],
                [
                    'data' => [
                        'confirm' => 'Are you sure you want to delete your account?',
                        'method' => 'post'
                       
                    ],
                    'class'  => 'app-btn btn btn-danger'
                ]  ) 
          ?>
         
          
          <?= Html::a(
              'Logout',
              ['/site/logout'],
              ['data-method' => 'post', 'class' => 'app-btn btn btn-danger']
          ) 
          ?>
        <?php  }
          ?>
      </div>
    </div>
  </div>
</section>
<!-- End App_Store --> 



<!-- End Featured_ads --> 



<!-- We_Bes -->
<!--
<section class="we_bes p-b-45">
  <div class="container"> 
    
    <div class="row justify-content-center">
      <div class="col-md-7 text-center">
        <h2 class="title">Why We Are Best</h2>
        <h6 class="subtitle">Explore the greates places in the city.</h6>
      </div>
    </div>
    
    <div class="row">
      <div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
        <div class="d-flex m-t-40">
          <div class="counter_icon mr-3"><i class="fa fa-eye"></i> </div>
          <div class="counter_number">
            <h3> Eye on Quality </h3>
            <p> Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's. </p>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
        <div class="d-flex m-t-40 justify-content-between">
          <div class="counter_icon mr-3"><i class="fa fa-lock"></i> </div>
          <div class="counter_number">
            <h3> Protection Guaranteed </h3>
            <p> Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's. </p>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
        <div class="d-flex m-t-40">
          <div class="counter_icon mr-3"><i class="fa fa-comments"></i></div>
          <div class="counter_number">
            <h3> 24/7 Support </h3>
            <p> Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's. </p>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
        <div class="d-flex m-t-40">
          <div class="counter_icon mr-3"><i class="fa fa-laptop"></i></div>
          <div class="counter_number">
            <h3> Prompt Complaint Response </h3>
            <p> Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's. </p>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
        <div class="d-flex m-t-40 d-flex">
          <div class="counter_icon mr-3"><i class="fa fa-check-square-o"></i></div>
          <div class="counter_number">
            <h3> Verified Ads </h3>
            <p> Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's. </p>
          </div>
        </div>
      </div>
      <div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">
        <div class="d-flex m-t-40 d-flex">
          <div class="counter_icon mr-3"><i class="fa fa-leaf"></i></div>
          <div class="counter_number">
            <h3> Secure Payment Gateway </h3>
            <p> Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's. </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
-->
<!-- End We_Bes --> 

