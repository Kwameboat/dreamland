<?php
use kartik\social\Module;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \common\models\LoginForm */

use yii\helpers\Html;
// use yii\bootstrap\ActiveForm;
use yii\widgets\ActiveForm;
use common\widgets\Alert;
use yii\authclient\widgets\AuthChoice;


$this->title = 'Login';
$this->params['breadcrumbs'][] = $this->title;
?>

<!-- breadcrumb -->
<!--
<div class="iner_breadcrumb bg-light p-t-20 p-b-20">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#"><?=Yii::t('app','Home')?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?=Yii::t('app','Register/Sign In')?></li>
      </ol>
    </nav>
  </div>
</div>-->
<!-- End breadcrumb -->


<div class="clear-20"></div>

<section id="Contact_form">
    <div class="container">
        <div class="contacts_mape">
            <div class="row justify-content-center">

                <div class="col-md-6">
                <?php $form = ActiveForm::begin(); 
                $email = @$_GET['email'];
                $model->email = $email;
                // print_r($model);
                ?>
                    <?php //  echo  Alert::widget() ?>

                    <div class="modal-content">

                        <div class="modal-header">
                       
                            <h5 class="modal-title"><?=Yii::t('app','Login')?></h5>
                        </div>
                        <div class="modal-body">
                            
                        <?= $form->field($model, 'email')->textInput() ?>
                        <?php if(@$_GET['otpsent']==1){
                          echo $form->field($model, 'verification_token')->textInput();
                        }
                        ?>

                        </div>
                        <div class="register text-center">
                            <?php // Html::a(Yii::t('app','Register'), ['site/signup']) ?> </div>
                          
                            <?= Html::submitButton('Login', ['class' => 'btn btn-success']) ?>
                       
                    </div>
                    <?php ActiveForm::end(); ?>
                    <?php $authAuthChoice = AuthChoice::begin(['baseAuthUrl' => ['site/auth']]); ?>
                  <ul style="list-style-type: none; padding-left: 0px; display: flex;justify-content: center;">
                  <?php foreach ($authAuthChoice->getClients() as $client): ?>
                  <li  style="padding-right: 10px; "><?= $authAuthChoice->clientLink($client) ?></li>
                  <?php endforeach; ?>
                  </ul>
            <?php AuthChoice::end(); ?>
                </div>
                
            </div>
        </div>

    </div>
</section>
<div class="clear-20"></div>