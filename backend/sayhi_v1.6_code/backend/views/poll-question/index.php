<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Poll Question';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            <div class="box-body">
            <div class="pull-right">
            <?php 
              if(isset($searchModel->poll_id)){
                  echo Html::a('Create', ['create','poll_id' => $searchModel->poll_id], ['class' => 'btn btn-success pull-right']); 
              }else{
                  echo Html::a('Create', ['create'], ['class' => 'btn btn-success pull-right']);
              }    
           ?>
            </div>
                <div style="clear:both"></div>


                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        'title',
                        [
                            'attribute'  => 'poll_id',
                            'value' => function($model){
                                return @$model->poll->title;
                                
                                
                            },
                            'filter'=>Html::activeDropDownList($searchModel, 'poll_id', $categoryData,['class'=>'form-control','prompt' => 'All']),
                            'format'=>'raw'
                        ],  
                        [
                            'attribute'  => 'id',
                            'label' =>'Vote Count' ,
                            'value' => function($model){
                                return (int) @$model->totalVoteCount;
                                
                                
                            },
                           
                        ],  
                       
                         [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{view} {update} {delete} {viewoptions}',
                             'urlCreator' => function ($action, $model, $key, $index) {

                                if($action === 'update') {
                                    $url = Url::to(['poll-question/update', "id" => $model['id']]);
                                    return $url;
                
                                }
                                if($action === 'delete') {
                                    $url = Url::to(['poll-question/delete', "id" => $model['id']]);
                                    return $url;
                
                                }
                
                                if($action === 'view') {
                                    $url = Url::to(['poll-question/view', "id" => $model['id']]);
                                    return $url;
                
                                }

                                if($action === 'viewoptions') {
                                    $url = Url::to(['poll-question-option/', "PollQuestionOptionSearch[question_id]" => $model['id']]);
                                    return $url;
                
                                }
                
                            },
                
                            'buttons' => [
                
                                'viewoptions' => function ($url, $model, $key) {
                
                                    return Html::a('<span class="glyphicon glyphicon-list"></span>', $url, ['title' => 'View Poll Options']);
                
                                },
                            ],
                         ],
                    
                    ],
                    'tableOptions' => [
                        'id' => 'theDatatable',
                        'class' => 'table table-striped table-bordered table-hover',
                    ],
                ]); ?>
            </div>


        </div>
        <!-- /.box -->



        <!-- /.col -->
    </div>
</div>