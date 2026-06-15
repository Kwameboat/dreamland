<?php

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Club';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            <div class="box-body">
               
                <div style="clear:both"></div>


                <?= GridView::widget([
                    
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        'name',

                        [
                            'attribute'  => 'category_id',
                            'value' => function($model){
                                return @$model->clubCategory->name;
                                
                                
                            },
                            'filter'=>Html::activeDropDownList($searchModel, 'category_id', $categoryData,['class'=>'form-control','prompt' => 'All']),
                            'format'=>'raw'
                        ],


                        
                        
                        [
                            'label'  => 'Jointed Users',
                            'value'  => function ($data) {
                                return count($data->clubUser);
                            },
                            'format'=>'raw'
                        ],
                       
                        [
                            'label'  => 'Created By',
                            'value'  => function ($data) {
                                
                                return $data->user->username;
                            },
                            'format'=>'raw'
                        ],
                        'created_at:datetime',
                        [
                            'attribute'  => 'status',
                            'value'  => function ($data) {
                                return $data->statusButton;
                            },
                            'format'=>'raw'
                        ],
                        
                        [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{view}  {delete}',
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