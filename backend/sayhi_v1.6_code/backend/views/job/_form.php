<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
// use Yii;
/* @var $this yii\web\View */
/* @var $model app\models\Countryy */
/* @var $form yii\widgets\ActiveForm */
// echo Yii::$app->urlManagerFrontend->baseUrl;
//echo  Url::base(true);
?>

<div class="countryy-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->field($model, 'organization_id')->dropDownList($organizationData,['prompt' => 'Select']); ?>
    <?= $form->field($model, 'category_id')->dropDownList($categoryData,['prompt' => 'Select']); ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'skill')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'type')->dropDownList($model->getJobTypeDropDown()); ?>
    <?= $form->field($model, 'country_id')->dropDownList($countryList,['prompt' => 'Select','id' => 'country-id']); ?>
    <?= $form->field($model, 'state_id')->dropDownList([], ['prompt' => 'Select', 'id' => 'state-id']); ?> 
    <?= $form->field($model, 'city_id')->dropDownList([],['prompt' => 'Select', 'id' => 'city-id']); ?>
    <?= $form->field($model, 'education')->textArea(['maxlength' => true,'rows'=>2]); ?>
    <?= $form->field($model, 'experience_min')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'experience_max')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'salary_min')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'salary_max')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'status')->dropDownList($model->getStatusDropDownData()); ?>

    <?= $form->field($model, 'description')->textArea(['maxlength' => true,'rows'=>6]) ?>
    
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>


<?php

$js=<<< JS


  // Function to load state options
  function loadStateOptions(countryId,savedStateId) {
        var stateListUrl = "index.php?r=job%2Fstate-list";
        if (countryId) {
            $.ajax({
                url: stateListUrl,
                type: 'GET',
                data: { countryId: countryId },
                dataType: 'json',
                success: function (data) {
                    // console.log("Data received for state dropdown:", data);
                    $('#state-id').html(data);

                    // Automatically select the saved state after loading data
                    if (savedStateId) {
                        $('#state-id').val(savedStateId);
                        savedStateId = null; // Reset savedStateId to prevent repeated selection
                    }
                }
            });
        } else {
            $('#state-id').html('<option value="">Select</option>');
        }
    }

    // Function to load city options
    function loadCityOptions(stateId,savedCityId) {
        var cityListUrl = "index.php?r=job%2Fcity-list";
        if (stateId) {
            $.ajax({
                url: cityListUrl,
                type: 'GET',
                data: { stateId: stateId },
                dataType: 'json',
                success: function (data) {
                    $('#city-id').html(data);

                    // Automatically select the saved city after loading data
                    if (savedCityId) {
                        $('#city-id').val(savedCityId);
                        savedCityId = null; // Reset savedCityId to prevent repeated selection
                    }
                }
            });
        } else {
            $('#city-id').html('<option value="">Select</option>');
        }
    }
    // get state
    $('#country-id').on('change', function(){
        var countryId = $(this).val();
        var stateListUrl = "index.php?r=job%2Fstate-list";
          if(countryId){
            $.ajax({
                url: stateListUrl,
                type: 'GET',
                data: {countryId: countryId},
                dataType: 'json',
                success: function(data){
                    $('#state-id').html(data);
                }
            });
        }else{
            $('#state-id').html('<option value="">Select</option>');
        }
    });
    // get city name
    $('#state-id').on('change', function(){
        var stateId = $(this).val();
        var cityListUrl = "index.php?r=job%2Fcity-list";
          if(stateId){
            $.ajax({
                url: cityListUrl,
                type: 'GET',
                data: {stateId: stateId},
                dataType: 'json',
                success: function(data){
                    $('#city-id').html(data);
                }
            });
        }else{
            $('#city-id').html('<option value="">Select</option>');
        }
    });
  $(document).ready(function(){
    var savedCityId = null;
    var savedStateId = null;
    var countryId = null;
   
        var savedStateId = "$model->state_id";
  
        var savedCityId = "$model->city_id";
    
        var countryId = "$model->country_id";
  
    // var savedCityId = isset($model->city_id) ? $model->city_id : null;
    // var countryId = $model->country_id;
    loadStateOptions(countryId,savedStateId);

    loadCityOptions(savedStateId,savedCityId);

  });

JS;
$this->registerJs($js,\yii\web\view::POS_READY);
?>
