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
</style>
<div class="countryy-form">

    <?php $form = ActiveForm::begin(); ?>

    
        <?php foreach($featureList as $key=>$features){ ?>
            <div class="panel panel-default">
        <div class="panel-heading">
            <h4><?php echo $sections[$key]; ?></h4>
        </div>
        <div  class="row" style="padding:10px;">
            <?php 
                foreach($features as $record){
                    
                ?>
        
                    <div class="col-sm-3">
                    <?php  echo $form->field($model, 'feature[]')->checkBox(['label' => $record['name'],"readonly"=>true,'disabled'=>($record['is_disable'])?true:false, 'checked'=>($record['is_active'])?true:false, 'data-size'=>'small', 'style'=>'margin-bottom:4px;','value'=> $record['id']]);?>
                        <?php // echo $form->field($model, 'is_photo_post')->checkBox(['label' => $record['name'], 'data-size' => 'small', 'style' => 'margin-bottom:4px;']);?>  
                    </div>
            <?php 
         } ?>

        </div>
        </div>
        <?php  } ?>
                
      
    

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<?php
$js = <<<JS
    // alert('a')
    $("form").submit(function() {
    $("input").removeAttr("disabled");
});
  

JS;
    $this->registerJs($js, \yii\web\view::POS_READY);
    ?>