<?php

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Support Request';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            <div class="box-body">
                
                <div style="clear:both"></div>


                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        
                        [
                            'label'  => 'User',
                            'value'  => function ($model) {
                                
                                return Html::a(@$model->user->username, ['/user/view', 'id' => $model->user_id]);
                            },
                            'format'=>'raw'
                        ],
                        'name',
                        'email',
                        'phone',
                        [
                            'label'  => 'Support Request',
                            'value'  => function ($model) {
                                
                                
                                $string = substr($model->request_message, 0, 30);
                                if(strlen($model->request_message)>30){
                                    $string .='...';
                                }
                                return $string;


                                
                            },
                            'format'=>'raw'
                        ],

                        'created_at:datetime',
                        
                        
                       
                        [
                            'attribute'  => 'is_reply',
                            'value'  => function ($data) {
                                return $data->isReplyButton;
                            },
                            'format'=>'raw'
                        ],
                        
                        [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{view} {delete}',
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