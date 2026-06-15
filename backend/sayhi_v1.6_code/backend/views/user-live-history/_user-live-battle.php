<?php

use common\models\UserLiveHistory;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\widgets\ListView;
use yii\helpers\Url;
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


<?php
// echo "<prE>";
// print_r($model->attributes['coin']);
$modelUserliveHistory = new UserLiveHistory();

// echo "<prE>";
// print_r($model);
$userLiveId = $model->user_live_history_id;
$battleId = $model->id;
$superHost_userId = $model->super_host_user_id;
$host_userId = $model->host_user_id;
$superhostTotalCoin = $modelUserliveHistory->getTotalCoinFromBattle($userLiveId, $battleId, $superHost_userId);
$hostTotalCoin = $modelUserliveHistory->getTotalCoinFromBattle($userLiveId, $battleId, $host_userId);
$superHostName =  $modelUserliveHistory->getUserName($superHost_userId);
$hostName =  $modelUserliveHistory->getUserName($host_userId);
$winnerName = '';
if ($superhostTotalCoin >= $hostTotalCoin) {
  $winnerName = $superHostName;
} else {
  $winnerName = $hostName;
}
// echo $coin =$modelUserliveHistory->getTotalCoinFromBattle(429,305,122);
?>

<!-- Display any other content or HTML structure as needed -->


<!-- Display the data for each item here -->
<div class="">

  <div class="row row1 border">
<div class="col-md-12">
    <div class="col-md-6 align-items-center">
      <div class="box_2">

        <h5 class="boxHead"><b>Battle <?= $incrementingId ?></b></h5>

        <table class="table table-striped">

          <tr>
            <td><b>Winner</b></td>
            <td><?= $winnerName ?></td>

          </tr>
          <tr>
            <td><b>Start Time</b></td>
            <td><?= date('d/m/Y h:i A', $model->start_time); ?>
            </td>
          </tr>
          <tr>
            <td><b>End Time</b></td>
            <td><?= date('d/m/Y h:i A', $model->end_time); ?></td>
          </tr>
          <td><b>Total Time</b></td>
          <td> <?= $modelUserliveHistory->getTimeInHrs($model->total_time); ?></td>
          </tr>
        </table>
      </div>
    </div>
    <div class="col-md-6"></div>
  </div>




<div class="row host">
<div class="col-md-4 align-items-center bg-gray my_box">
  <div class="box_1">
    <h5><b>Host 1</b></h5>
    <div class="row">
      <div class="col-md-6">
        <ul class="listItem">
          <li>Name</li>
        </ul>
      </div>
      <div class="col-md-6">
        <ul class="listItem">
          <li><?= $superHostName ?></li>
        </ul>
      </div>
      <div class="col-md-6">
        <ul class="listItem">
          <li>Total Received Coin</li>
        </ul>
      </div>
      <div class="col-md-6">
        <ul class="listItem">
          <li>
            <?php echo Html::a($superhostTotalCoin, ['user-live-history/livegift-history-detail', 'liveCallId' => $userLiveId, 'recieverId' => $superHost_userId, 'battleId' => $battleId], [
              'class' => 'loadDataButton',
              'id' => 'loadDataButton' . $battleId . $superHost_userId,
              'data-toggle' => 'modal',
              'data-target' => '#myModal', // The ID of the Bootstrap Modal
            ]); ?>
          </li>
        </ul>
      </div>
      <div class="col-md-6"></div>
    </div>
  </div>
</div>
<div class="col-md-2"></div>
<div class="col-md-4 align-items-center bg-gray my_box">
  <div class="box_1">
    <h5><b>Host 2</b></h5>
    <div class="row">
      <div class="col-md-6">
        <ul class="listItem">
          <li>Name</li>
        </ul>
      </div>
      <div class="col-md-6">
        <ul class="listItem">
          <li><?= $hostName ?></li>
        </ul>
      </div>
      <div class="col-md-6">
        <ul class="listItem">
          <li>Total Received Coin</li>
        </ul>
      </div>
      <div class="col-md-6">
        <ul class="listItem">
          <li>
            <?php echo Html::a($hostTotalCoin, ['user-live-history/livegift-history-detail', 'liveCallId' => $userLiveId, 'recieverId' => $host_userId, 'battleId' => $battleId], [
              'class' => 'loadDataButton',
              'id' => 'loadDataButton' . $battleId . $host_userId,
              'data-id' => $battleId . $host_userId,
              'data-toggle' => 'modal',
              'data-target' => '#myModal', // The ID of the Bootstrap Modal
            ]); ?>
          </li>
        </ul>
      </div>
      <div class="col-md-6"></div>
    </div>
  </div>
</div>
<div class="col-md-2"></div>
</div>
</div>
</div>

</div>
<!-- Display any other attributes or data as needed -->
<style>
  ul.listItem {
    list-style-type: none;
    padding-left: 0;
  }

  .bg-gray {
    background-color: #d8d7d7 !important;
  }

  .row1 {
    width: 100%;
    margin: 0 auto;
    padding: 10px;
  }

  .row.border {
    border: 1px solid;
  }

  .my_box {
    padding: 12px;
  }

  .row.host{
    
    padding-bottom: 20px;
    margin: 0px !important;
}
.row.row1.border {
    padding-bottom: 0px !important;
}
.row.border {
    border: 1px solid #d8d7d7;
    margin-top: 10px;
}
</style>
<?php
// Assuming you have a container to display the GridView

// echo '<div id="gridViewContainer"></div>';
// $this->registerJs('
//     $(".loadDataButton").click(function(event) {
//         event.preventDefault(); // Prevent the default behavior of the link

//         var url = $(this).attr("href");

//         $.get(url, function(data) {
//             $("#myModal").html(data); // Load the data into the Bootstrap Modal
//             $("#myModal").modal("show"); // Show the Bootstrap Modal
//         });
//     });
// ');


?>