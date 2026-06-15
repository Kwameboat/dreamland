<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Reported Post';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            <div class="box-body">
                <!--<div class="pull-right m-bottom"><?= Html::a('Create', ['create'], ['class' => 'btn btn-success pull-right ']) ?></div>-->
                <div style="clear:both"></div>


                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        'title',
                        [
                            'attribute'  => 'user_id',
                            'label' => 'Username',
                            'value' => function($model){
                                
                                return Html::a(@$model->user->username, ['/user/view', 'id' => @$model->user_id]);
                            },
                            'filter'=>Html::activeDropDownList($searchModel, 'user_id', $userData,['class'=>'form-control','prompt' => 'All']),
                            'format'=>'raw'
                        ],
                      
                        'total_view',
                        'total_like',
                        'popular_point',
                        'total_comment',
                        
                        [
                            'attribute'  => 'status',
                            'value'  => function ($data) {
                                return $data->getStatus();
                            },
                        ],
                      
                       'created_at:datetime',
                         [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{view}',
                             'urlCreator' => function ($action, $model, $key, $index) {

                                if ($action === 'view') {
                                  
                                    $url = 'index.php?r=post/view-reported-post&id='.$model['id'];

                                    return $url;

                                }
 
                              

                            },
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