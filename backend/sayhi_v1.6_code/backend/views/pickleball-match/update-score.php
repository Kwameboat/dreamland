<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
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
<h3>Update Score</h3>
<button type="button" class="close" data-dismiss="modal">&times;</button>

<div class="col-md-12">
     
     <div class="box_2">
       <div>
       <table class="table table-stripeds">

       <tr>
         <th width="100px"><b>Team :</b></th>
         <th><b><?= $model->name ?></b></th>
        
       </tr>
       
       </table>
      
       </div>
      
    <!--  <form method="post" action="./pickleball-match/update-score">-->
      <?php $form = ActiveForm::begin(); ?>
        
       <table class="table table-stripeds">

         <tr class="bg-gray">
           <th ><b>Player</b></th>
           <th><b>Score</b></th>
         </tr>
         <?php foreach($model->teamPlayer as $player){ ?>
         <tr>
           <td><?= $player->playerDetail->username?></td>
           <td><input type="text"  name="player_id[<?=$player->id?>]" value="<?= $player->point_gain?>">
            </td>
         </tr>
         <?php } ?>
         <tr>
           <td></td>
           <td><input  type="submit" class="btn btn-success"  name="fname" value="Save">
           &nbsp;
           <button type="button" class="btn btn-cancel hidemodal" data-bs-dismiss="#myModal">Cancel</button>
           </td>
           
         </tr>  
         <tr>
           <td>&nbsp;</td>
          
           
         </tr>  
        
       </table>
       <!--</form>-->
       <?php ActiveForm::end(); ?>
     </div>
   </div>
  
 </div>
<?php
//print_r($data);
    
?>
</div>
</div>
<?php

// Assuming you have a container to display the GridView


$this->registerJs('

console.log("bbb");
    $(".hidemodal").click(function(event) {
    console.log("a");
       
            $("#myModal").modal("hide"); // Show the Bootstrap Modal
       
    });
    
');


?>
