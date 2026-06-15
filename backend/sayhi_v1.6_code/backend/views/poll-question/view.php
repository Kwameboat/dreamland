<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\data\ArrayDataProvider;
use kartik\grid\GridView;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Poll Question Detail : '. $model->title;
$this->params['breadcrumbs'][] = ['label' => 'User', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
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
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
        <?= Html::a('Create Options', ['poll-question-option/create', 'ques_id' => $model->id], ['class' => 'btn btn-success']) ?>
        <?= Html::a('View All Options', ['poll-question-option/', 'PollQuestionOptionSearch[question_id]' => $model->id], ['class' => 'btn btn-primary hidden']) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'title',
            
             [
                'attribute'  => 'poll_id',
                'value' => function($model){
                    return @$model->poll->title;
                },
            ],
            [
                'attribute'  => 'id',
                'label' =>'Total Question Vote Count' ,
                'value' => function($model){
                    return (int) @$model->totalVoteCount;
                    
                    
                },
               
            ], 
        ],
    ]) ?>

<?php
echo '<br>';
               
                
               echo  GridView::widget([
                  'dataProvider' => new ArrayDataProvider([
                    //   'allModels' => $model->optionDetail,
                      
                  ]),
                 // 'filterModel' => $searchModel,
                  'columns' => [
                      //['class' => 'yii\grid\SerialColumn'],
                     // ['class' => 'kartik\grid\SerialColumn'],
                        [
                            'label'  => 'ID',
                            'attribute'  => 'id',
                            'value' => function($model){
                                
                                 return ''.$model->id;
                                
                            
                            },
                            'format'=>'raw'
                        ],
                       'title',
                       [
                        'attribute'  => 'id',
                        'label' =>'Vote Count' ,
                        'value' => function($model){
                            return (int) @$model->totalOptionVoteCount;
                            
                            
                        },
                       
                    ], 
                        // [
                        //     'attribute'  => 'user_id',
                        //     'label' => 'User',
                        //     'value' => function($model){
                                
                        //       return @Html::a($model->userName->name, ['/user/view', 'id' => $model->user_id]);
                        //     },
                        //     'format'=>'raw'
                        // ],
                        // [
                        //     'attribute'  => 'poll_question_id',
                        //     'value' => function($model){
                        //         return @$model->questionName->title;                                
                                
                        //     },
                        // ],
                        // [
                        //     'attribute'  => 'question_option_id',
                        //     'label' => 'Option',
                        //     'value' => function($model){
                        //         return @$model->optionName->title;
                                
                                
                        //     },
                        // ],                          
                        // 'created_at:datetime',
                  
                  ],
                  'tableOptions' => [
                      'id' => 'theDatatable',
                      'class' => 'table table-striped table-bordered table-hover',
                  ],
                  'toolbar' => [
                  ],

                  'pjax' => false,
                  'bordered' => false,
                  'striped' => false,
                  'condensed' => false,
                  'responsive' => true,
                  'hover' => true,
                  'floatHeader' => false,
                  //'floatHeaderOptions' => ['top' => $scrollingTop],
                  'showPageSummary' => false,
                  'summary'=> false,
                  
                  'panel' => [
                      'type' => GridView::TYPE_PRIMARY,
                      'heading'=>'Question Options',
                      
                  ],
                                 
              ]); 
             
              ?>

</div>


</div>

</div>
</div>
