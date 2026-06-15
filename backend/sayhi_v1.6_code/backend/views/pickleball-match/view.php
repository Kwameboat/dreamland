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

$this->title = 'Match Details';
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
                    'attribute'  => 'status',
                    'value'  => function ($data) {
                        return $data->getStatus();
                    },
                ],
                'start_time:datetime',
                [
                    'attribute'  => 'match_type',
                    'value'  => function ($data) {
                        return $data->getType();
                    },
                ],
                [
                    'attribute'  => 'court_id',
                    'value'  => function ($data) {
                        return $data->court->name;
                    },
                ],
                'point_to_win',
                [
                    'label' => 'Is Result Declared?',
                    'attribute'  => 'winner_team_id',
                    'value'  => function ($data) {
                        if($data->winner_team_id){
                            return 'Yes';
                        }else{
                            return 'No';
                        }
                        
                    },
                ],
                'result_declared_at:datetime'
                
        ],
       
    ]) ?>
    </div>
    </div>
    </div>
<h3>Teams Summery</h3>
<div class="box">
<div class="box-body">
<div class="box-header col-xs-12">
<div class="user-live-battle">
<?php  
//print_r($model->matchTeam);
//die;
    $dataProvider = new ArrayDataProvider([
        'allModels' => $model->matchTeam, // Passing the related teams
        'pagination' => [
            'pageSize' => 10, // Set the pagination if needed
        ],
    ]);
    $incrementingId = 1;
    echo ListView::widget([
        'dataProvider' => $dataProvider,
        
        'itemView' =>  function ($model, $key, $index, $widget) use (&$incrementingId) {
            return $this->render('_team-detail', [
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


 #myModal .container {
    background: #fff;
    width: 500px;
    
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
    $(".hidemodal").click(function(event) {
    console.log("a");
      

       
            $("#myModal").modal("hide"); // Show the Bootstrap Modal
       
    });
    
');


?>