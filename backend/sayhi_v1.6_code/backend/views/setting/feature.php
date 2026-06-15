<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
?>
<style>
.column {
  float: left;
  padding: 10px;
  margin-left: 1.5%;
}
.row:after {
  content: "";
  display: table;
  clear: both;
}
</style>
<div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h4>Feature availability</h4>
        </div>
            <div  class="row">
                <div class="column ">
                    <?php echo $form->field($model, 'is_photo_post')->checkBox(['label' => 'Enable Photo post', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_video_post')->checkBox(['label' => 'Enable Video post', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_stories')->checkBox(['label' => 'Enable Stories', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_story_highlights')->checkBox(['label' => 'Enable Story highlights', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_audio_calling')->checkBox(['label' => 'Enable Audio calling', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?> 
                    <?php echo $form->field($model, 'is_video_calling')->checkBox(['label' => 'Enable video calling', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_live')->checkBox(['label' => 'Enable Live', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_coupon')->checkBox(['label' => 'Enable Coupon', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_job')->checkBox(['label' => 'Job', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                     
                </div>
                <div class="column">
                    </div>
                <div class="column">
                     <?php echo $form->field($model, 'is_clubs')->checkBox(['label' => 'Enable clubs', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                     <?php echo $form->field($model, 'is_profile_verification')->checkBox(['label' => ' Enable profile verification', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_light_mode_switching')->checkBox(['label' => 'Enable Dark/light mode switching', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_watch_tv')->checkBox(['label' => 'Enable Watch Tv', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_podcasts')->checkBox(['label' => ' Enable Podcasts', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_gift_sending')->checkBox(['label' => ' Enable Gift Sending', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_events')->checkBox(['label' => 'Enable events', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_fund_raising')->checkBox(['label' => 'Enable Fund Raising', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_shop')->checkBox(['label' => 'Shop', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                </div>
                <div class="column">
                    <?php echo $form->field($model, 'is_staranger_chat')->checkBox(['label' => 'Enable Staranger chat', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_competitions')->checkBox(['label' => 'Enable competitions', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_chat')->checkBox(['label' => 'Enable chat', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_photo_share')->checkBox(['label' => 'Photo sharing', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_video_share')->checkBox(['label' => 'Video Share', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_files_share')->checkBox(['label' => 'File Share', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_gift_share')->checkBox(['label' => 'Gif Share', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_live_user')->checkBox(['label' => 'Live User', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_offer')->checkBox(['label' => 'Offer', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                   
                </div>
                <div class="column">
                   <?php echo $form->field($model, 'is_audio_share')->checkBox(['label' => 'Audio Share', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_drawing_share')->checkBox(['label' => ' Drawing Share', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_user_profile_share')->checkBox(['label' => 'User Profile Share ', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_club_share')->checkBox(['label' => 'Club Share', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_events_share')->checkBox(['label' => 'Event Share', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_reply')->checkBox(['label' => 'Reply', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_forward')->checkBox(['label' => 'Forward', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_star_message')->checkBox(['label' => 'Star Message', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_photo_video_edit')->checkBox(['label' => 'Photo/Video editable', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    
                   
                </div>

                <div class="column">
                  
                    <?php echo $form->field($model, 'is_contact_sharing')->checkBox(['label' => 'Contact Share', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_location_sharing')->checkBox(['label' => 'Location Share', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_polls')->checkBox(['label' => 'Polls', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_dating')->checkBox(['label' => ' Dating', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php // echo $form->field($model, 'is_fund_raising')->checkBox(['label' => 'Fund raising ', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_family_link_setup')->checkBox(['label' => 'Family Link Setup', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    <?php echo $form->field($model, 'is_post_promotion')->checkBox(['label' => 'Post Promotion', 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_chat_gpt')->checkBox(['data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    <?php echo $form->field($model, 'is_reel')->checkBox(['data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>
                    
                    
                   
                </div>
          </div>            
      
    </div>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<?php
$js = <<<JS
    // alert('a')
   
  

JS;
    $this->registerJs($js, \yii\web\view::POS_READY);
    ?>