<?php

use yii\helpers\Html;
$this->title = 'Update Orgnization  : ' . $model->name;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-body">
                <?= $this->render('_form', [
                    'model' => $model,
                    'categoryData' =>$categoryData,
                    
                ]) ?>
            </div>
        </div>
    </div>
</div>