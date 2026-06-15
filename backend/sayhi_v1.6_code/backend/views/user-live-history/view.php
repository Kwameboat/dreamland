<?php

use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\widgets\ListView;
use common\models\GiftHistory;
use common\models\UserLiveBattle;
use yii\data\ActiveDataProvider;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */

$this->title = 'User live Details';
$this->params['breadcrumbs'][] = ['label' => 'User', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>


<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>

            </div>
             -->
    <div class="box-body">
    <div class="col-xs-6">
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute'  => 'reciever_id',
                'label' => 'Name',
                'value' => function ($model) {
                    return Html::a($model->user->name , ['/user/view', 'id' => @$model->user->id]);
                },
                // 'filter'=>Html::activeDropDownList($searchModel, 'reciever_id', $userData,['class'=>'form-control','prompt' => 'All']),
                'format'=>'raw'
               ],
               [
                'attribute'  => 'reciever_id',
                'label' => 'Username',
                'value' => function ($model) {
                    return Html::a($model->user->username , ['/user/view', 'id' => @$model->user->id]);
                },
                // 'filter'=>Html::activeDropDownList($searchModel, 'reciever_id', $userData,['class'=>'form-control','prompt' => 'All']),
                'format'=>'raw'
               ],
               'start_time:datetime',
               'end_time:datetime',
            //    'total_time',
               [
                'attribute'  => 'total_time',
                'label' => 'Total Time',
                'value' => function ($model) {

                    $seconds = $model->total_time;
                    $secs = @$seconds % 60;
                    $hrs = (int)($seconds / 60);
                    $mins = @$hrs % 60;
                    $hrs = @$hrs / 60;
                    // "HH:MM:SS-> " 
                    return ( (int)$hrs . " hr :" . (int)$mins . " min :" . (int)$secs.' sec');
                
                },
                ],
                 [
                    'attribute'  => 'id',
                    'label' => 'Total Received Coin',
                    'value' => function ($model) {
                        $total_coin=0.00;
                        foreach(@$model->giftDetails as $giftData){
                            $total_coin += number_format(round((float)$giftData->coin,2),2); 
                        }
                        // return $total_coin;
                        return Html::a($total_coin, ['user-live-history/livegift-history-detail', 'liveCallId' => $model->id, 'recieverId' =>$model->user_id ], [
                            'class' => 'loadDataButton',
                            'id' => 'loadDataButton',
                            'data-toggle' => 'modal',
                            'data-target' => '#myModal', // The ID of the Bootstrap Modal
                        ]);
                    },
                    'format'=>'raw'
                    ],
        ],
       
    ]) ?>
    </div>
    </div>
    </div>
<h3>Battle Summery</h3>
<div class="box">
<div class="box-body">
<div class="box-header col-xs-12">
<div class="user-live-battle">
<?php  
    $incrementingId = 1;
    echo ListView::widget([
        'dataProvider' => $dataProvider,
        'itemView' =>  function ($model, $key, $index, $widget) use (&$incrementingId) {
            return $this->render('_user-live-battle', [
                'model' => $model,
                'key' => $key,
                'index' => $index,
                'incrementingId' => $incrementingId++,
            ]);
        },

        // '_user-live-battle', // The view file to render for each item
        'options' => ['class' => 'list-view'], // Add any additional options here
        'itemOptions' => ['class' => 'list-view-item'], // Add any additional item options here
    ]);

   ?>
   <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
   
   </div>     
             
    </div>
</div>        
</div>
</div>

</div>
</div>
<style>
#myModal table {
    width: 100%;
    margin: 0 auto;
    background-color: #fff;
}
.table > tbody > tr > td {
   
    text-align: left !important;
}
button.close {
    padding: 0;
    cursor: pointer;
    background: transparent;
    border: 0;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    position: absolute;
    top: 21px;
    right: 22%;
}

.container {
    background: #fff;
}
.container h3 {
    text-align: center;
}
</style>
<?php

// Assuming you have a container to display the GridView

echo '<div id="gridViewContainer"></div>';
$this->registerJs('
// Use a delegated event handler for pagination links
$("#myModal").on("click", ".pagination a", function(event) {
    event.preventDefault(); // Prevent the default behavior of the link

    var url = $(this).attr("href");

    $.get(url, function(data) {
        $("#myModal").html(data); // Load the new page content into the Modal
    });
});
    $(".loadDataButton").click(function(event) {
        event.preventDefault(); // Prevent the default behavior of the link

        var url = $(this).attr("href");

        $.get(url, function(data) {
            $("#myModal").html(data); // Load the data into the Bootstrap Modal
            $("#myModal").modal("show"); // Show the Bootstrap Modal
        });
    });
');


?>