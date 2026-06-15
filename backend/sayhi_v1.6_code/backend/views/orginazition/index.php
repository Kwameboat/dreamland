<?php
use yii\grid\GridView;
use yii\helpers\Html;
$this->title = 'Organization List';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- /.box-header -->
            <div class="box-body">
                <div class="pull-right m-bottom"><?= Html::a('Create', ['create'], ['class' => 'btn btn-success pull-right ']) ?></div>
                <div style="clear:both"></div>


                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        'name',
                      
                       
                        [
                            'attribute'  => 'address',
                            'value'  => function ($data) {
                                return $data->address;
                            },
                            'format'=>'raw'
                        ],
                        
                        [
                            'attribute'  => 'phone',
                            'value'  => function ($data) {
                                return $data->phone;
                            },
                            'format'=>'raw'
                        ],
                       
                        [
                            'attribute' => 'image',
                            'format' => 'html',    
                            'value' => function ($data) {
                                return Html::img($data->imageUrl, ['width' => '70px','height' => '60px']);
                            },
                        ],




                        
                        [
							'class' => 'yii\grid\ActionColumn',
							 'header' => 'Action',
                             'template' => '{update} {delete}',
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