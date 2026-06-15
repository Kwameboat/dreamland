<?php
use yii\helpers\Html;

?>


<?php
// echo "<prE>";
// print_r($model->attributes['coin']);
/*$modelUserliveHistory = new UserLiveHistory();

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
}*/
// echo $coin =$modelUserliveHistory->getTotalCoinFromBattle(429,305,122);
?>

<!-- Display any other content or HTML structure as needed -->


<!-- Display the data for each item here -->
<div class="col-md-6">

  <div class="row row1 border">
    <div class="col-md-12">

      <div class="box_2">
        <div>
          <table class="table table-stripeds">
            <tr>
              <th width="200px"></th>
              <th style="text-align: right; ">

                <?php
                if ($model->winner_status == $model::WINNER_STATUS_NOT_DECLARE) {

                  echo Html::a('Declare as Winner', ['declare-result', 'teamId' => $model->id], [
                    'class' => 'btn btn-success',
                    'data' => [
                      'confirm' => 'Are you sure you want to declare winner this team?',
                      'method' => 'post',
                    ],
                  ]);

                }else if ($model->winner_status == $model::WINNER_STATUS_WIN) {?>
                  <span class="label label-success">Won</span>
                  <?php
                }else if ($model->winner_status == $model::WINNER_STATUS_LOSS) {
                  ?>
                  <span class="label label-danger">Loss</span>
                <?php 
                }


                ?>
                <?php echo Html::a('Update Score', ['pickleball-match/update-score', 'teamId' => $model->id], [
                  'class' => 'loadDataButton btn btn-primary btn-sm float-right',
                  'id' => 'loadDataButton',
                  'data-toggle' => 'modal',
                  'data-target' => '#myModal', // The ID of the Bootstrap Modal
                ]); ?>
              </th>

            </tr>

            <tr>
              <th><b>Team :</b></th>
              <th><b><?= $model->name ?></b></th>

            </tr>
            <tr>
              <th><b>Total Score :</b></th>
              <th><b><?php echo $model->team_point ?></b></th>

            </tr>
          </table>

        </div>


        <table class="table table-stripeds">

          <tr class="bg-gray">
            <th><b>Player</b></th>
            <th><b>Score</b></th>
          </tr>
          <?php foreach ($model->teamPlayer as $player) { ?>
            <tr>
              <td><?= $player->playerDetail->username ?></td>
              <td><?= $player->point_gain ?></td>
            </tr>
          <?php } ?>


          </tr>
        </table>
      </div>
    </div>

  </div>
</div>
</div>

</div>

<style>
  /*
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

  .row.host {

    padding-bottom: 20px;
    margin: 0px !important;
  }

  .row.row1.border {
    padding-bottom: 0px !important;
  }*/

  .row.border {
    border: 1px solid #d8d7d7;

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