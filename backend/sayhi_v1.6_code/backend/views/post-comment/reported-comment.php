<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\CountryySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Reported Comment';
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
                        'comment',
                       
                        [
                            'attribute'  => 'post_id',
                            'value'  => function ($data) {
                                return Html::a(@$data->post->title, ['/post/view', 'id' => @$data->post_id]);
                                // return $data->post->title;
                            },
                            'format'=>'raw'
                        ],
                        [
                            'attribute'  => 'user_id',
                            'label' => 'Username',
                            'value' => function($model){
                                
                                return Html::a(@$model->user->username, ['/user/view', 'id' => @$model->user_id]);
                            },
                            'filter'=>Html::activeDropDownList($searchModel, 'user_id', $userData,['class'=>'form-control','prompt' => 'All']),
                            'format'=>'raw'
                        ],
                      
                       'created_at:datetime',
                         [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{view}',
                             'urlCreator' => function ($action, $model, $key, $index) {

                                if ($action === 'view') {
                                  
                                    $url = 'index.php?r=post-comment/view-reported-post-comment&id='.$model['id'];

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