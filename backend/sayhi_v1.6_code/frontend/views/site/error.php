<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;
$this->title = $name;

$this->params['breadcrumbs'][] = Yii::t('app',$this->title);
?>

<section class="error_section">
<div class="container">
<div class="error_parts text-center">
<div class="row  justify-content-center">
<div class="col-md-12 col-lg-6 col-sm-12">
<h3> 404 </h3>
<h4> Oop, that link is broken. </h4>
<?= Yii::t('app',nl2br(Html::encode($message))) ?>
<p> We are sorry, but something went wrong </p>

<div class="form-group col-md-12 col-sm-12 col-xs-12  m-t-40">
                      <button class="btn btn-primary btn-skin back" name="submit" onClick="parent.location='<?= Yii::$app->homeUrl?>'" type="submit"> Back to homepage</button>
                    </div>

</div>
</div>



</div>
</div>
</section>

