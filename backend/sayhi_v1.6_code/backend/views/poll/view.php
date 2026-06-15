<?php

use common\models\Poll;
use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\data\ArrayDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'Poll Detail : '. $model->title;
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
                'attribute'  => 'category_id',
                'value' => function($model){
                    return @$model->category->name;
                },
            ],
            [
                // 'attribute'  => 'type',
                'label' =>'Poll start time to end time' ,
                'value' => function($model){
                    if($model->type == Poll::TYPE_POLL){
                        $startTime = date('d/m/Y h:i A', $model->start_time);
                        $endTime = date('d/m/Y h:i A', $model->end_time);
                        return $startTime . ' - ' . $endTime;
                    }
                    return null;
                    
                    
                },
               
            ], 
            // 'start_time:datetime',
            // 'end_time:datetime', 
            // 'type', 
            // 'created_by_poll', 
            [
                'attribute'  => 'type',
                // 'label' =>'Total Poll Vote Count' ,
                'value' => function($model){
                    return @$model->getType();
                    
                    
                },
               
            ],
            [
                'attribute'  => 'created_by_poll',
                // 'label' =>'Total Poll Vote Count' ,
                'value' => function($model){
                    return @$model->getPollCreatedBy();
                    
                    
                },
               
            ],
            'description',
            [
                'attribute'  => 'id',
                'label' =>'Total Poll Vote Count' ,
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
                      'allModels' => $model->optionDetail,
                      
                  ]),
                 // 'filterModel' => $searchModel,
                  'columns' => [
                      ['class' => 'yii\grid\SerialColumn'],
                     // ['class' => 'kartik\grid\SerialColumn'],
                       
                       'title',
                       [
                        'attribute'  => 'id',
                        'label' =>'Vote Count' ,
                        'value' => function($model){
                            return (int) @$model->totalOptionVoteCount;
                            
                            
                        },
                       
                    ], 
                    [
                        'class' => 'yii\grid\ActionColumn',
                         'header' => 'Action',
                         'template' => '{update} {delete}',
                         'urlCreator' => function ($action, $model, $key, $index) {
            

                            if ($action === 'update') {
            
                                $url = Url::to(['poll-question-option/update', "id" => $model['id']]);
                                return $url;
            
                            }
                            
                            if ($action === 'delete') {
            
                                $url = Url::to(['poll-question-option/delete', "id" => $model['id']]);
                                return $url;
            
                            }
            
                        },
            
                        'buttons' => [
            
                            'episodelist' => function ($url, $model, $key) {
            
                                return Html::a('<span class="fa fa-eye fa-lg"></span>', $url, ['title' => 'Podcast Episode List']);
            
                            },
                        ],
                     ],
        
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
                      'heading'=>'Options',
                      
                  ],
                                 
              ]); 
             
              ?>

</div>


</div>

</div>
</div>
