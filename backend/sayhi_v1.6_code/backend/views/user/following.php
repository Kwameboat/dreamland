<?php
//use yii\grid\GridView;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Users Total following';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12"><div class="box">
            <!-- <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>

            </div>-->
            <!-- /.box-header -->
            <div class="box-body">
                <!--<div class="pull-right"><?= Html::a('Create', ['create'], ['class' => 'btn btn-success pull-right']) ?></div>
                <div style="clear:both"></div>-->
               
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'kartik\grid\SerialColumn'],
                        [
                            'attribute'  => 'name',
                            'value'  => function ($data) {
                            //     echo "<pre>";
                            //    print_r($data->followerUserDetail);
                            //    exit("ff");
                               $followername = $data->followingUserDetail->getAttribute('name');
                               $followerId = $data->followingUserDetail->getAttribute('id');
                                return Html::a($followername , ['/user/view', 'id' => $followerId]);
                            },
                        'format'=>'raw'
                        ],
                        [
                            'attribute'  => 'username',
                            'value'  => function ($data) {

                               $followerUsername = $data->followingUserDetail->getAttribute('username');
                               $followerId = $data->followingUserDetail->getAttribute('id');
                                return $followerUsername;
                            },
                        'format'=>'raw'
                        ],
                       
                        [
                            'class' => 'yii\grid\ActionColumn',
                             'header' => 'Action',
                             'template' => '{view}',
                             'urlCreator' => function ($action, $model, $key, $index) {
                               $user_id = $model->followingUserDetail->getAttribute('id');
                                if($action === 'view') {
                                    $url = Url::to(['user/view', "id" => $user_id]);
                                    return $url;
                                }
                
                            },
                         ],
                       
                    ],
                    'tableOptions' => [
                        'id' => 'theDatatable',
                        'class' => 'table table-striped table-bordered table-hover',
                    ],
                     'toolbar' => [
                    
                        [
                            
                        ],
                        //'{export}',
                        //'{toggleData}'
                    ],
                    //'toggleDataContainer' => ['class' => 'btn-group-sm'],
                    //'exportContainer' => ['class' => 'btn-group-sm'],
                    'exportConfig' => [
                        GridView::CSV => ['label' => 'CSV'],
                        GridView::EXCEL => [],// html settings],
                       
                    ],
                   

                    /*
                    'toolbar' =>  [
                        ['content'=>
                            Html::button('&lt;i class="glyphicon glyphicon-plus">&lt;/i>', ['type'=>'button', 'title'=>Yii::t('app', 'Add Book'), 'class'=>'btn btn-success', 'onclick'=>'alert("This will launch the book creation form.\n\nDisabled for this demo!");']) . ' '.
                            Html::a('&lt;i class="glyphicon glyphicon-repeat">&lt;/i>', ['grid-demo'], ['data-pjax'=>0, 'class' => 'btn btn-default', 'title'=>Yii::t('app', 'Reset Grid')])
                        ],
                    ],*/   
                    
                    'pjax' => false,
                    'bordered' => true,
                    'striped' => false,
                    'condensed' => false,
                    'responsive' => true,
                    'hover' => true,
                    'floatHeader' => false,
                    //'floatHeaderOptions' => ['top' => $scrollingTop],
                    'showPageSummary' => false,
                    'panel' => [
                        // 'type' => GridView::TYPE_PRIMARY
                    ],
                   
                ]); ?>
            </div>


        </div>
        <!-- /.box -->



        <!-- /.col -->
    </div>
</div>