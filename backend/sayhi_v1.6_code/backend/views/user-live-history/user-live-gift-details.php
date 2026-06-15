<?php

use common\models\UserLiveHistory;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\widgets\ListView;
use yii\grid\GridView;
// use yii\widgets\DetailView;
// use yii\grid\GridView;
// use yii\widgets\ListView;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

// $this->title = 'User live history Gift Details';
// $this->params['breadcrumbs'][] = ['label' => 'User', 'url' => ['index']];
// $this->params['breadcrumbs'][] = $this->title;
// \yii\web\YiiAsset::register($this);
?>


<div class="rowbox-body">
<div class="container">
<p>&nbsp;</p>
<h3>Gift History Summery</h3>
<button type="button" class="close" data-dismiss="modal">&times;</button>
<?php
echo GridView::widget([
  'dataProvider' => $dataProvider,
  'columns' => [
    //   'id',
      [
        'attribute'  => 'reciever_id',
        'enableSorting' => false,
        'label' => 'Recieved User',
        'value' => function ($model) {
            return Html::a(@$model->user->username , ['/user/view', 'id' => @$model->user->id]);
        },
        'format'=>'raw',
        
       ],
       [
        'attribute'  => 'sender_id',
        'enableSorting' => false,
        'label' => 'Sender User',
        'value' => function ($model) {
            return Html::a(@$model->senderUser->username , ['/user/view', 'id' => @$model->senderUser->id]);
        },
        'format'=>'raw'
       ],
       [
        'attribute' => 'gift_id',
        'enableSorting' => false,
        'label' => 'Gift Image',
        'format' => 'html',    
        'value' => function ($model) {
            // $image = '<a href="#" title="title to show">';
            $image = Html::img(@$model->giftImageUrl, ['width' => '40px','height' => '30px','title'=>@$model->giftDetail->name]);
            // $image .= '</a>';
            return $image;
        },
       ],
      
       [
        'attribute' => 'coin',
        'enableSorting' => false,
        'label' => 'Coin',
        'format' => 'html',    
        'value' => function ($model) {
            return @$model->coin;
        },
       ],
       [
        'attribute' => 'created_at',
        'enableSorting' => false,
        'format' => ['date', 'php:d/m/Y g:i A'], // Format as "11/06/2023 8:05 PM"
         ],
       
      
  ],
  'pager' => [
    'class' => \yii\widgets\LinkPager::class,
    'prevPageLabel' => 'Previous',
    'nextPageLabel' => 'Next',
    'maxButtonCount' => 5, // Adjust the number of pagination links shown
  ],
  'tableOptions' => [
    'id' => 'theDatatable',
    'class' => 'table table-striped table-bordered table-hover',
],
]);
    
?>
</div>
</div>
