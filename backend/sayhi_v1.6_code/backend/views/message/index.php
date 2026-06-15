<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Message';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- /.box-header -->
            <div class="box-body">

                <div class="message_sec card">
                    <div class="row">
                        <div class="col-md-5 col-sm-5 message">
                            <div class="message-contact">

                                <div class="offers_main">
                                    <div class="list-unstyled">

                                    <?php if(count($resultGroup)>0) {

                                            foreach ($resultGroup as $group) {
                                                // echo '<pre>';

                                                //   print_r($group->receiverUser->imageThumb);
                                                // die;
                                                if ($group->receiver_id == $userId) {
                                                    $userImage = $group->senderUser->imageThumb;
                                                    $userName = $group->senderUser->name;

                                                } else {
                                                    $userImage = $group->receiverUser->imageThumb;
                                                    $userName = $group->receiverUser->name;
                                                }
                                              // echo substr($group->lastMessage->message,10); 
                                                ?>

                                        <a href="<?= Url::toRoute(['/message', 'group_id' => $group->id])?>">
                                            <div class="d-flex mt-3 <?=($group->id==$groupId)?'active':'';?>">
                                                <span class="pl-2">
                                                    <img class="mr-2" src="<?=$userImage?>" alt="user" />

                                                </span>
                                                <div class="box_name">
                                                    <div style="float:left">


                                                        <h4><?=$userName?></h4>
                                                        <p class="lastmessage"><?php
                                                        echo substr(@$group->lastMessage->message,0,20);
                                                        if(strlen(@$group->lastMessage->message)>20){
                                                               echo '...'; 
                                                        }
                                                        ?>

                                                        </p>
                                                        <p class="lastmessage-time">
                                                            <?=Yii::$app->formatter->asDatetime(@$group->lastMessage->created_at)?>
                                                        </p>
                                                    </div>
                                                    <div style="float:right">

                                                        <img class="mr-2" src="<?=$group->ad->mainImage?>" alt="ad" />
                                                    </div>


                                                </div>




                                            </div>
                                        </a>


                                        <?php }
                                        
                                                    }else{

                                                        echo '&nbsp;No chat history available';
                                                    }
                                        
                                        ?>




                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7 col-sm-7 chat" id="chat-box">
                            <?php 
                                if($resultAd){
                                ?>

                            <div class="chat_message">
                                <a href="">
                                    <div class="chat_header">
                                        <a href="<?= Url::toRoute(['/ad/view', 'id' => $resultAd->id])?>">
                                            <span class="float-left"><img class="ml-2" src="<?=$resultAd->mainImage?>"
                                                    alt="user" /></span>
                                            <div class="box_name_chat">

                                                <h4><?=$resultAd->title?></h4>
                                                <p class="lastmessage-time">
                                                    <?=Yii::$app->formatter->asDatetime($resultAd->created_at)?>
                                                </p>

                                            </div>
                                        </a>

                                    </div>




                                    <div class="chat_sec">
                                        <ul id="message-list" class="px-3">


                                            <?php
                                            if(count($resultMessage)>0){
                                            foreach ($resultMessage as $message) {
                                                if($message->sender_id == $userId){

                                                    //$liClass='justify-content-end';
                                                    $liClass='';
                                                    $contentClass='bubble-right';
                                                    $innerContentClass='main_content_2';

                                                } else{

                                                    $liClass='';
                                                    $contentClass='bubble-left';
                                                    $innerContentClass='main_content_1';
                                                }
                                                
                                                ?>
                                            <li class="<?=$liClass?>"">
                                              
                                                <div class=" content_chat <?=$contentClass?>">
                                                <div class="<?=$innerContentClass?>">
                                                    <h4 class="mb-0"><?=$message->senderUser->name?></h4>
                                                    <p class="mb-0"><?=$message->message?> </p>
                                                </div>
                                                <span>
                                                    <?=Yii::$app->formatter->asDatetime($message->created_at)?></span>
                                    </div>
                                    </li>
                                    <?php }
                                            }else{

                                               ?>

                                    <li style="padding-left:10px" >
                                        <?=Yii::t('app','No message history')?>
                                    </li>
                                    <?php 

                                            }
                                            ?>


                                    </ul>
                            </div>

                            <?php $form = ActiveForm::begin(['id'=> 'userProfileForm']); ?>
                            <div class="chat_message_bottum d-flex justify-content-between border-top">


                                <div class="message_type pl-2">
                                    <?= $form->field($modelMessage, 'message')->textInput(['class'=>'border-0','placeholder'=>"Type Your Message"])->label(false) ?>

                                </div>
                                <div class="message_send">
                                    <?php 
                                           
                                           echo $form->field($modelMessage, 'group_id')->hiddenInput(['value'=> $groupId])->label(false);
                                            echo $form->field($modelMessage, 'ad_id')->hiddenInput(['value'=> $adId])->label(false);

                                            echo Html::submitButton(Yii::t('app','Send'),['class'=>'btn btn-success']);
                                            ?>

                                </div>

                            </div>
                            <?php ActiveForm::end(); ?>

                        </div>
                        <?php 
                                }
                                ?>
                    </div>
                </div>

            </div>


        </div>
        <!-- /.box -->



        <!-- /.col -->
    </div>
</div>
<script>
$(function() {

    $("body").scrollTop($(".dashboard_heding").offset().top);

    $('.chat_sec').animate({
        scrollTop: $("#message-list li").last().offset().top
    }, 'fast');
});
</script>

<style>
/*---------------------------------------------Offers_Messages--------------------------------------*/
.float-left {
    float: left;
}

.bubble-left,
.bubble-right {
    line-height: 100%;
    display: block;
    position: relative;
    padding: .25em .7em;
    -webkit-border-radius: 11px;
    -moz-border-radius: 11px;
    border-radius: 11px;
    margin-bottom: 2em;
    clear: both;
    max-width: 50%;
}

.bubble-left {
    float: left;
    margin-right: 10%;
}

.bubble-right {
    float: right;
    margin-left: 10%
}


.devider {
    border-bottom: 1px solid #aaaaaa;
}

.offers_main h3 {
    font-size: 19px;
    color: #3a3a3a;
}

.offers_main h4 {
    font-size: 17px;
    color: #3a3a3a;
    margin-bottom: 0;
}

.offers_main .message-contact p {
    font-size: 14px;
    color: #aaaaaa;
    margin-bottom: 20px;
}

.offers_main .message-contact form {
    padding: 10px;
}

.offers_main .table th {
    padding: 8px 0px;
}

.offers_main .message-contact input {
    width: 150px;
    font-size: 16px;
    color: #aaaaaa;
}


.message_sec {
    min-height:100px;
}

.message_sec input {
    border: none;
    font-size: 16px;
    padding: 15px 10px;
    width: 100%;
}

.message_sec input:focus {
    outline: 0px;
}

.message_sec .message-contact .box_name h4 {
    font-size: 16px;
    color: #3a3a3a;
    margin-bottom: 0;
    font-weight: 300;
}

.message_sec .message-contactul img,
.message_sec .meagan_sms img,
.message_sec .message-contact img {
    border-radius: 50%;
    max-height: 42px;
}

.message_sec .message-contact .box_name p {
    font-size: 15px;
    color: #aaaaaa;
    margin-bottom: 0px;
}

.message_sec .message {
    padding-right: 0;


}

.message_send {
    padding-top: 4px;
    padding-right: 20px;
}

.card {
    border: 1px solid #aaaaaa;
}

.message_sec .chat {
    padding-left: 0;
    border-left: 1px solid #aaaaaa;

}

.message_sec .d-flex.float-right {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%)
}

.message_sec .message-contact .box_name .online {
    color: #ff6b6b;
}

.message_sec .message-contact .active,
.message-contact .offers_main .d-flex:hover {
    background: #f7fafc;
}

.message-contact .offers_main .d-flex:hover a {
    text-decoration: none;
}

.chat_message_bottum {
    padding: 10px 0px;
    border-top: 1px solid;
}

.chat_message_bottum .message_send button {

    font-size: 16px;
    padding: 5px 10px;

    border: none;
    color: #fff;
    cursor: pointer;
    font-weight: 400;
    letter-spacing: 1px;
    float: right;
}

.message_type input {
    font-size: 16px;
    color: #aaaaaa;
    padding: 10px 10px;
}

.chat_message h3 {
    font-size: 23px;
    font-weight: 300;
    letter-spacing: 1px;
    color: #3a3a3a;
    padding: 22px 25px;
}

.message_type input::placeholder {
    color: #aaaaaa;
}

.message_type input:focus {
    outline: 0px;
}

.messages_heding h3 {
    font-size: 17px;
    font-weight: 600;
    color: #3a3a3a;
    margin-bottom: 20px;
}

.chat_message_bottum .message_send {
    font-size: 21px;
    color: #aaaaaa;
    padding-right: 10px;

}

.chat_message_bottum .message_type {
    float: left
}

.chat_sec {
    height: 375px;

    overflow-y: auto;
}


.chat_sec ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.chat_sec ul li h4,
.single__wedget.popular__post ul li p {
    font-size: 13px;
    color: #3a3a3a;
    font-weight: 600;
}

.chat_sec ul li p {
    color: #949393;
    line-height: 19px;
    font-size: 13px;
    margin-top: 5px;
}

.chat_sec ul li span {
    font-size: 12px;
    font-style: italic;
    color: #3a3a3a;
    font-weight: 400;
    padding-left: 10px;
}

.chat_sec ul li {
    /*display: flex;*/
    letter-spacing: 1px;
    margin-top: 20px;
    margin-bottom: 30px;
}

.chat_sec ul li img {
    border-radius: 50%;
}

.chat_sec .content_chat {
    width: 100%;
    max-width: 265px;
    margin: 0px 10px;
    font-size: 13px;
    line-height: 2;
}

.content_chat .main_content_1 {
    background: #f7fafc;
    border-radius: 4px;
    padding: 1px 9px;
    color: #949393;
}

.content_chat .main_content_2 {
    background: #40da8b;
    border-radius: 4px;
    padding: 1px 9px;
    color: #fff;
    text-align: right
}

.content_chat .main_content_2 h4 {
    color: #fff;
}

.content_chat .main_content_2 p {
    color: #fff;
}

.message_send .fa {
    font-size: 21px;
    color: #aaaaaa;
    padding: 0 3px;
}

.message_sec .message-contact .d-flex {
    padding: 10px 10px;
    margin: 5px 0px;
    height: 87px;
}


.message-contact .lastmessage {
    font-size: 13px !important;
    font-style: italic;
}

.message-contact .lastmessage-time {
    font-size: 13px !important;
}

.message_sec .message-contact .box_name {
    width: 86%;
    float: right;
}

.chat_message .chat_header {

    padding: 0px 10px;
    border-bottom: 1px solid;
}

.chat_message .chat_header img {
    border-radius: 50%;
    max-height: 42px;
}


.chat_message .chat_header .box_name_chat {
    padding-left: 60px;

}


.chat_message .chat_header .box_name_chat h4 {
    font-size: 15px;

}

.chat_message .chat_header .box_name_chat p {
    color: #aaaaaa;

    font-size: 13px;



}

.btn_chat {
    text-align: center;
}

.offers_main {

    max-height: 570px;
    overflow-y: auto;
}
</style>