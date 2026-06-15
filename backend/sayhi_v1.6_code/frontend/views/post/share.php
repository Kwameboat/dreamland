<?php
use yii\helpers\Html;
use yii\helpers\Url;

//print_r($postResult);

?>
 


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
      <div class="col-xl-8 col-lg-8 col-md-8 col-sm-6 col-12">
    
        <div class="d-flex justify-content-center m-t-40" style="background-color:#EEF0E5;">
          <div id="myCarousel" class="carousel slide" data-ride="carousel">
            <!-- Wrapper for slides -->
            <!--<ol class="carousel-indicators">
                        <?php
                         $i=0;
                        foreach($postResult->postGallary as $record){ 
                            $classAdd ='';
                            if($i==0){
                                $classAdd ='active';
                            }
                            ?>
                        <li data-target="#myCarousel" data-slide-to="<?=$i?>" class="<?=$classAdd?>"></li>
                        
                        <?php 
                        $i++;
                        } ?>
                    </ol>-->
            <div class="carousel-inner">
              <?php

              if (count($postResult->postGallary) > 0) {
                 $record = $postResult->postGallary[0];
                $i = 0;
              //  foreach ($postResult->postGallary as $record) {
                  $classAdd = '';

                  if ($i == 0) {
                    $classAdd = 'active';
                  }
                  ?>
                  <div class="item <?= $classAdd ?>">

                    <?php

                    if ($record->media_type == 1 || $record->media_type == 4  ) {
                      
                      echo Html::img($record->filenameUrl, ['alt' => 'No Image', 'width' => "100%"]);

                    }
                    elseif ($record->media_type == 2  ) {
                      //poster="'.$record->videoThumbUrl.'"
                
                      echo '<video  width="100%" style="max-height:450px" controls >
                            <source src="' . $record->filenameUrl . '" type="video/mp4">
                            <source src="movie.ogg" type="video/ogg"></video>';
                    } elseif ($record->media_type == 3) { //audio
                      echo '<br>';
                      
                      echo '<audio controls>
                        <source src="' . $record->filenameUrl . '" type="audio/ogg">
                        <source src="' . $record->filenameUrl . '" type="audio/mpeg">
                      Your browser does not support the audio element.
                      </audio>';
                      echo '<br>';
                      echo '<br>';
                     
                    }

                    ?>

                  </div>

                  <?php
              //  }


                $i++;
              }

              ?>
            </div>
          </div>
        </div><br>
        <h5>
          <?= $postResult->title ?>
        </h5>
      </div>

      <div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 col-12">


        <div class="d-flex m-t-40">
          <div class="counter_icon"><i class="fa fa-comments"></i></div>
          <div class="counter_numbers">
            <h3> Comments </h3>
          </div>



        </div>
        <div class="container ">
          <div class="comment_container">
            <?php // print_r($postResult->postComment)
            foreach ($postResult->postComment as $comment) { ?>
              <div class="media pb-1" style="clear: both;">
                <div class="media-left">
                  <img src="<?= $comment->user->imageUrl ?>" class="media-object" style="width:30px;height:30px" >
                </div>
                <div class="media-body">
                  <h4 class="media-heading title_user">
                    <?= $comment->user->username ?> <span class="comment-date">
                      <?= Yii::$app->formatter->asDateTime($comment->created_at); ?>
                    </span>
                  </h4>
                  <p class="komen">

                    <?= $comment->comment ?>

                  </p>
                </div>
              </div>

              <?php

              foreach ($comment->childComment as $childComment) { ?>
                <div class="geser">
                  <div class="media pb-1" style="clear: both;">
                    <div class="media-left">
                      <img src="<?= $childComment->user->imageUrl ?>" class="media-object" style="width:30px;height:30px">
                    </div>
                    <div class="media-body">
                      <h4 class="media-heading title_user">
                        <?= $childComment->user->username ?> <span class="comment-date">
                          <?= Yii::$app->formatter->asDateTime($comment->created_at); ?>
                        </span>
                      </h4>
                      <p class="komen">

                        <?= $childComment->comment ?>

                      </p>
                    </div>
                  </div>
                </div>
              <?php }
            } ?>



          </div>
        </div>


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