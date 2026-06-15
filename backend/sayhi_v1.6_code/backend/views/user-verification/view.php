<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\data\ArrayDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'User Verification Request Detail:';
$this->params['breadcrumbs'][] = ['label' => 'Package', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
//\yii\web\YiiAsset::register($this);
?>


<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>

            </div>
             -->
            <div class="box-body">



    <p>
    
    <?php if($model->status== $model::STATUS_PENDING){ ?>
        
        <?= Html::a('Accept and Verified', ['update', 'id' => $model->id,'status'=>10], [
            'class' => 'btn btn-success',
            'data' => [
                'confirm' => 'Are you sure you want verified?',
                'method' => 'post',
            ],
        ]) ?>


       
        <?php  echo Html::a('Reject', ['reject', 'id' => $model->id], ['class' => 'btn btn-danger']) ?>
        <?php }?>
        
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            
            [
                'attribute'  => 'status',
                'value' => function($model){
                    return $model->statusButton;
                },
                'format'=>'raw'
                
            ],
            [
                'attribute'  => 'user_id',
                'value' => function($model){
                    
                    return Html::a($model->user->name, ['/user/view', 'id' => $model->user_id]);
                },
                'format'=>'raw'
            ],
           
            
            'user_message',        
            'admin_message',            
            'created_at:datetime',
            'updated_at:datetime',
            'document_type',
          
        ],
    ]) ?>

    <p>
        <h3>Verification Documents</h3>

        <?php 
        foreach($model->verificationDocument as $document){
           // print_r($document);
            echo '<br>';
            if($document->media_type==1){
                //return $photo->imageUrl;
                echo $document->title.'<br>';
               echo Html::img($document->filenameUrl, ['alt' => 'No Image', 'width' => '600px',  'style'=>"border 1px solid red; padding:5px;"]);   
            }else if($model->media_type==2){
                echo $document->title.'<br>';
              echo '<video width="600" poster="'.$document->filenameUrl.'"  height="100" controls>
                <source src="'.$document->imageUrl.'" type="video/mp4">
                <source src="movie.ogg" type="video/ogg"></video>' ;
                
            }
            echo '<br>';
            
        }
        
        ?>


    </p>



       
             

</div>


</div>

</div>
</div>
