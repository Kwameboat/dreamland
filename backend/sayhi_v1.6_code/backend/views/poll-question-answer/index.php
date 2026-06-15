<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Poll Question Answer';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            <div class="box-body">
            <div class="pull-right">
            <?php 
            //   if(isset($searchModel->poll_id)){
            //       echo Html::a('Create', ['create','poll_id' => $searchModel->poll_id], ['class' => 'btn btn-success pull-right']); 
            //   }else{
            //       echo Html::a('Create', ['create'], ['class' => 'btn btn-success pull-right']);
            //   }    
           ?>
            </div>
                <div style="clear:both"></div>


                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],     
                        [
                            'attribute'  => 'user_id',
                            'value' => function($model){
                                
                              return @Html::a($model->userName->name, ['/user/view', 'id' => $model->user_id]);
                            },
                            'format'=>'raw'
                        ],               
                        
                        [
                            'attribute'  => 'poll_question_id',
                            'value' => function($model){
                                return @$model->questionName->title;                                
                                
                            },
                        ],   
                        [
                            'attribute'  => 'question_option_id',
                            'value' => function($model){
                                return @$model->optionName->title;
                                
                                
                            },
                        ],                   
                        'created_at:datetime',
                        [
                            'attribute'  => 'poll_id',
                            'value' => function($model){
                                return @$model->poll->title;
                                
                                
                            },
                            'filter'=>Html::activeDropDownList($searchModel, 'poll_id', $categoryData,['class'=>'form-control','prompt' => 'All']),
                            'format'=>'raw'
                        ],  
                       
                         [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{delete}',
                            
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