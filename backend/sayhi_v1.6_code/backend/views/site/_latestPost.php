<?php
use yii\helpers\Url;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\data\ArrayDataProvider;


/* @var $this yii\web\View */


?>
<div class="box box-info">
  <div class="box-header with-border">
    <h3 class="box-title">Latest Posts</h3>

    <div class="box-tools pull-right">
      <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
      </button>
      <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
    </div>
  </div>
  <!-- /.box-header -->
  <div class="box-body">
    <div class="table-responsive">


      <?= GridView::widget([
        'dataProvider' => new ArrayDataProvider([
          'allModels' => $postLatest,

        ]),
        'columns' => [
          ['class' => 'yii\grid\SerialColumn'],
          [
            'attribute' => 'title',
            'value' => function ($model) {
                    if($model->title){
                      //return mb_strimwidth($model->title, 0, 40, "...");
                    }
                    //     return $model->title;
                  },

            'format' => 'raw'
          ],
          [
            'attribute' => 'user_id',
            'label' => 'Username',
            'value' => function ($model) {

                    return Html::a(@$model->user->username, ['/user/view', 'id' => $model->user_id]);
                  },

            'format' => 'raw'
          ],


          'created_at:datetime',
          [
            'class' => 'yii\grid\ActionColumn',
            'header' => 'Action',
            'template' => '{view}',
            'urlCreator' => function ($action, $model, $key, $index) {
            

              if ($action === 'view') {

                  $url = Url::to(['post/view', "id" => $model['id']]);
                  return $url;

              }
              
           
          },
          ],

        ],
        'tableOptions' => [
          'id' => 'theDatatable',
          'class' => 'table table-striped',
        ],
        'summary'=> false
      ]); ?>


    </div>
    <!-- /.table-responsive -->
  </div>
  <!-- /.box-body -->
  <div class="box-footer clearfix">
      <?= Html::a('View All Posts', ['/post'],['class'=>'btn btn-sm btn-default btn-flat pull-right']);?>
    
  </div>
  <!-- /.box-footer -->
</div>