<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use yii\grid\GridView;
?>





    <?php $form = ActiveForm::begin(); ?>


    <style>

.image-mosaic {
  /* display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  grid-auto-rows: 240px; */

  display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-auto-rows: 150px;
    padding: 20px;
    grid-gap: 10px;

}

.card {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background: #353535;
  font-size: 3rem;
  color: #fff;
  box-shadow: rgba(3, 8, 20, 0.1) 0px 0.15rem 0.5rem, rgba(2, 8, 20, 0.1) 0px 0.075rem 0.175rem;
  height: 100%;
  width: 100%;
  border-radius: 4px;
  transition: all 500ms;
  overflow: hidden;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  padding: 0;
  margin: 0;
  position: relative;
  cursor: pointer;
}

.text-block p {
    text-align: left;
    width: 100%;
    margin-bottom: 0;
}
.text-block {
    position: absolute;
    font-size: 14px;
    color: #d4d4d4;
    /* / float: left; / */
    right: 0;
    background-color: #225806db;
    padding: 11px;
}

@media screen and (min-width: 600px) {
  .card-tall {
    grid-row: span 2 / auto;
  }

  .card-wide {
    grid-column: span 2 / auto;
  }
}

.modal-content h2 {
  margin-top: 0;
  color: #333;
  font-family: Tahoma, Arial, sans-serif;
}
.modal-content .close {
  position: absolute;
  top: 20px;
  right: 30px;
  transition: all 200ms;
  font-size: 30px;
  font-weight: bold;
  text-decoration: none;
  color: #333;
}
.modal-content .close:hover {
  color: #06D85F;
}
.modal-content .content {
  max-height: 30%;
  overflow: auto;
}

.overlay:target {
    visibility: visible;
    opacity: 1;
    background: rgb(28 25 25 / 70%);
}
.modal{
  display: none; 
  position: fixed; 
  z-index: 1; 
  padding-top: 100px;
  left: 0;
  top: 0;
  width: 100%; 
  height: 100%; 
  overflow: auto; 
  background-color: rgb(0,0,0); 
  background-color: rgba(0,0,0,0.4);
  
}
/* / Modal Content / */
.modal-content {
  background-color: #fefefe;
  margin: auto;
  padding: 20px;
  border: 1px solid #888;
  width: 30%;
  height: 200px;;
}

.card {
    position: relative;
}

.gallery__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}


/* / / select images / / */
.card:nth-child(4) {
    grid-column-start: span 2;
}

.card:nth-child(5) {
    grid-row-start: span 2;
}

.card:nth-child(9) {
    grid-column-start: span 2;
}


@media (min-width:768px) {
    .image-mosaic {
        grid-template-columns: repeat(4, 1fr);
    }

    .card:nth-child(3) {
        grid-column-start: span 2;
    }

    .card:nth-child(4) {
        grid-column-start: span 3;
    }

    .card:nth-child(5) {
        grid-row-start: span 3;
    }

    .card:nth-child(9) {
        grid-column-start: span 3;
    }
}


@media (min-width:1024px) {
    .image-mosaic {
        grid-template-columns: repeat(5, 1fr);
        width: 90%;
        margin: auto;
    }

    .card:nth-child(1) {
        grid-column-start: span 2;
        grid-row-start: span 2;
    }

    .card:nth-child(4) {
        grid-column-start: span 3;
        grid-row-start: span 3;
    }

}
</style>

<div class="row">
<div id="competitionwinpopup" class="modal">
<!-- popup -->
	<div class="modal-content">
  <div class="panel-body">
  <div  class="copy_container">
      <div class="form-group">
  <label class="control-label" for="competition-position">Select winner competition position</label>
		<a class="close" href="#">&times;</a>
		<div class="content">
   
      <input type="hidden" class="winner_post_ids"  id="winner_post_ids">
      <!--<input type="hidden" class="winner_user_id" name="winner_user_id" id="winner_user_id">
      <input type="hidden" class="competition_id" name="competition_id" id="competition_id">
      <input type="hidden" class="competition_position_ids" >-->
   <?php  
   //[$competitionPositionData[1]['id']
  /* echo Html::dropDownList("competition_position_id",'competition_id', $competitionPositionData,['class'=>'form-control possition_selected','prompt' => 'Select Competition Position','onchange'=>'
    var id =  $(this).val();
    var win_post_id = $(".winner_post_ids").val(); 
    $("#winner_post_id"+win_post_id).val(id);


   
   ']);*/
    echo Html::dropDownList("competition_position_id",'competition_id', $competitionPositionData,['class'=>'form-control possition_selected','prompt' => 'Select Competition Position']);
   echo '<br>';
   echo  Html::button('Select', ['class' => 'btn btn-success btn-select']);        
   
   ?>
   
		</div>
	</div>
  </div>
	</div>
  </div>

</div>


<div class=" panel-default">
    <!-- <div class="row"> -->
    <div class="image-mosaic">
            <?php 
            
            
                    $i=1;
                    // echo "<pre>";
                    // print_r($resultPost);
                    // exit;
                    foreach($resultPost as $data){
                      // echo "<pre>";
                      // foreach($data->postGallary as $imgData){
                      //    $img =$imgData->filenameUrl;
                      // }
                    //  echo $img;
                    // $data->imageUrl //old usrl
                   $postGalleryDefaultImg = @$data->postCompetitionGallary->filenameUrl;
                    echo  $html ='
                    
                      <div class="card" id="'.$data->id.'" data-userid="'.$data->user_id.'" data-competition_id="'.$data->competition_id.'">
                      <input type="hidden" id="winner_post_id'.$data->id.'" name="winner_post_id['.$data->id.']" >
                     '.  Html::img($postGalleryDefaultImg, ['alt' => 'No Image','width'=>"100%" , "class"=>"gallery__img" ]).'
                     <div class="text-block">
                        <p>Title : '.$data->title.'</p>
                        <p>Total Views : '.$data->total_view.'</p>
                        <p>Total Like	: '.$data->total_like.'</p>
                        <p>Popular Point : '.$data->popular_point.'</p>
                        <p>Total Comment: '.$data->total_comment.'	</p>
                        <p>Competition Position: '.$data->total_view.'	</p>
                      </div>
                    </div>';  

                    // echo Html::dropDownList("competition_position_id[$data->competition_id]",'competition_id', $competitionPositionData,['class'=>'form-control','prompt' => 'Select Competition Position']); 
                     
                    }


            ?>
      </div>
    </div>
    <div class="col-xs-6">







      
         

  <div class="form-group">
        <?php 
        if(!empty($resultPost)){
        echo  Html::submitButton('Save', ['class' => 'btn btn-success']);        
      }
      ?>
    </div>


   
    </div>
    <?php ActiveForm::end(); ?>

</div>
<?php
$js=<<< JS
  //  alert('a')  
  $(document).ready(function(){
    

  
  $('.btn-select').click(function(){
    console.log('btn')
    var win_post_id = $(".winner_post_ids").val();
    var possition_selected = $(".possition_selected").val();
    $("#winner_post_id"+win_post_id).val(possition_selected);
    $(".modal").hide();
  })

$('.card').click(function(){

  $(".modal").show();
  var id = $(this).attr('id');
  console.log(id);
  var userid = $(this).attr('data-userid');
  var competition_id = $(this).attr('data-competition_id');
  $(".possition_selected").val('');
  $(".winner_post_ids").val(id);
//  $(".winner_user_id").val(userid);
 // $(".competition_id").val(competition_id);
});

$(".close").click(function(){
  $(".modal").hide();
});

});

const test = (id)=>{
  alert("helo"+id);
}
// function test(id){
//   alert("helo"+id);
// }


JS;
$this->registerJs($js,\yii\web\view::POS_READY);
?>