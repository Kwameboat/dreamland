<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
?>
<style>
.column {
  float: left;
  padding: 10px;
  margin-left: 1.5%;
}
.row:after {
  content: "";
  display: table;
  clear: both;
}
.sub_action label{
    color:#898686;
    font-weight: normal;
}
</style>
<div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>

    
        
            <div class="panel panel-default">
        <div class="panel-heading">
            <h4>User Module Permission</h4>
        </div>
        <div  class="row" style="padding:10px;">
            <?php 
                foreach($moduleList as $record){
                    
                ?>
        
                    <div class="col-sm-5" style="margin:10px; border-bottom:1px solid #e7e2e2 ">
                    <?php  echo $form->field($model, 'module_ids[]')->checkBox(['label' => $record['name'],'checked'=>($record['is_active'])?true:false, 'data-size'=>'small', 'style'=>'margin-bottom:0px;','class'=>'main_module','value'=> $record['id']]);?>
                        <?php   foreach($record['child_action_list'] as $childRecord){?>
                        <div  class="sub_container" style="background-color:gray; margin-top:-14px ">
                            <div class="sub_action" style="background-color:yelllo; width:70px; float:left">
                            <?php  echo $form->field($model, 'module_ids[]')->checkBox(['label' => $childRecord['name'],'checked'=>($childRecord['is_active'])?true:false, 'data-size'=>'small','class'=>'sub_module', 'style'=>'margin-bottom:0px;','value'=> $childRecord['id']]);?>
                            
                            </div>
                          
                        </div>

                        <?php } ?>  
                    </div>
            <?php 
         } ?>

        </div>
        </div>
        
                
      
    

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<?php
$js = <<<JS
    // alert('a')
   
    $(document).ready(function () {

        $('.main_module').click(function () {
           
          console.log($(this).prop('checked'));
          if($(this).prop('checked')){
            $(this).parent().parent().parent().find('.sub_container').find('.sub_module').each(function( index ) {
                
                if($(this).prop('checked')){
                    console.log( index,'check');    
                }else{
                    console.log( index,'uncheck' );    
                    $(this).trigger("click");
                    
                }
                //$(this).trigger("click");
                /*if($(this).prop('checked')){
                    $(this).trigger("click");
                }else{

                }*/

            });
            //$(this).parent().parent().parent().find('.sub_container').find('.sub_module').removeAttr('checked').trigger("change");
            //$(this).parent().parent().parent().find('.sub_container').find('.sub_module').attr('checked','checked').trigger("change");
          }else{
            $(this).parent().parent().parent().find('.sub_container').find('.sub_module').each(function( index ) {
                
                if($(this).prop('checked')){
                    console.log( index,'check');    
                         $(this).trigger("click");
                }else{
                   // $(this).trigger("click");
                    //console.log( index,'uncheck' );    
               
                    
                }
                //$(this).trigger("click");
                /*if($(this).prop('checked')){
                    $(this).trigger("click");
                }else{

                }*/

            });
           
          //  $(this).parent().parent().parent().find('.sub_container').find('.sub_module').removeAttr('checked').trigger("change");
          }
          
          
        })
    })


JS;
    $this->registerJs($js, \yii\web\view::POS_READY);
    ?>