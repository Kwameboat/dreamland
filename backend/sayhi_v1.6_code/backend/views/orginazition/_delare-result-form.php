<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
?>

<div class="countryy-form">
    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
    <div class="col-xs-6">
            <?php 
                    $i=1;
                    foreach($model->competitionPosition as $competitionPosition){ ?>

                        
                        <div class="panel panel-default">
                            <div class="panel-heading">
                            <h4>Competition Position #<?=$i?> (<?=$competitionPosition->title?>)</h4>
                                    
                            </div>
                                
                            <div class="panel-body">
                                <div  class="copy_container">
                                    <div class="form-group">
                                  
                                    <label class="control-label" for="competition-price">Select winner post</label>

                                        <?php 

                                            $preValue = @$winnerPostIds[$competitionPosition->id];

                                          
                                                                                    
                                        echo Html::dropDownList("winner_post_id[$competitionPosition->id]",$preValue, $resultPostData,['class'=>'form-control','prompt' => 'Select']);
                                        //echo Html::activeDropDownList($form, 'user_id', $resultPostData,['class'=>'form-control','prompt' => 'All']);
                                        ?>    
                                   
                                    </div>   
                                
                                    
                                </div>
                            </div>
                        </div>

                    <?php
                    $i++;
                    }
            
            ?>

  

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>


   
    </div>
    <?php ActiveForm::end(); ?>

</div>
<?php
$js=<<< JS
  //  alert('a')
        
  $(document).ready(function(){
    function hideShowDiv() {
        var inputValue = $("#competition-award_type"). val();
        
        if(inputValue==2){
            $('#price_text_block').slideUp();
            $('#coin_text_block').slideDown();
        }else{
            $('#price_text_block').slideDown();
            $('#coin_text_block').slideUp();

            
            
        }
    }
    
    $('#competition-award_type').change(function(){
        hideShowDiv();
       
    });
    hideShowDiv();




  $(".add-more").click(function(){ 
      var html = $(".copy").html();
      $(".after-add-more").append(html);
  });


  $("body").on("click",".remove",function(){ 
      $(this).parents().parents(".copy_container").remove();
  });







});
JS;
$this->registerJs($js,\yii\web\view::POS_READY);
?>