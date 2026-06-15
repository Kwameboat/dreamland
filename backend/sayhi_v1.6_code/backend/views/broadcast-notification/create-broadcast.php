<?php
//use yii\grid\GridView;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\grid\CheckboxColumn;
use common\models\DreamlandAudience;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var string $audience */
/* @var array $audienceLabels */

$this->title = 'Send Broadcast Notifications';
$this->params['breadcrumbs'][] = $this->title;
?>


<style>
    .image-mosaic {
        display: grid;
        gap: 1rem;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        grid-auto-rows: 240px;
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
        /* float: left; */
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

    .modal {
        display: none;
        /* Hidden by default */
        position: fixed;
        /* Stay in place */
        z-index: 1;
        /* Sit on top */
        padding-top: 100px;
        /* Location of the box */
        left: 0;
        top: 0;
        width: 100%;
        /* Full width */
        height: 100%;
        /* Full height */
        overflow: auto;
        /* Enable scroll if needed */
        background-color: rgb(0, 0, 0);
        /* Fallback color */
        background-color: rgba(0, 0, 0, 0.4);
        /* Black w/ opacity */

    }

    /* Modal Content */
    .modal-content {
        background-color: #fefefe;
        margin: auto;
        padding: 20px;
        border: 1px solid #888;
        width: 30%;
    }
</style>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <!-- <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>

            </div>-->
            <!-- /.box-header -->
            <div class="box-body">
                <div class="btn-group" style="margin-bottom:12px;">
                    <?php foreach ($audienceLabels as $key => $label): ?>
                        <?php if ($key === DreamlandAudience::CUSTOM) continue; ?>
                        <?= Html::a($label, ['create', 'audience' => $key], [
                            'class' => 'btn btn-sm ' . ($audience === $key ? 'btn-primary' : 'btn-default'),
                        ]) ?>
                    <?php endforeach; ?>
                </div>
                <p class="text-muted">
                    Pick a target group below, or choose users manually and send with <strong>Custom selection</strong>.
                    <?= Html::a('Broadcast to entire group without picking rows', '#', [
                        'class' => 'btn btn-xs btn-success',
                        'id' => 'sendGroupButton',
                        'data-audience' => $audience,
                    ]) ?>
                </p>
                <div class="pull-right">

                    <?= Html::button('Send', ['class' => 'btn btn-success pull-right', 'id' => "sendButton", 'style' => 'margin-bottom:5px']) ?>
                </div>
                <div style="clear:both"></div>

                <?= GridView::widget([
                    'id' => 'grid',
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [

                        ['class' => 'kartik\grid\SerialColumn'],

                        ['class' => 'yii\grid\CheckboxColumn'],
                        'name',
                        'username',
                        'email',
                        [
                            'attribute' => 'dreamland_account_type',
                            'label' => 'Group',
                            'value' => static function ($data) {
                                if (in_array((int) $data->role, [1, 2], true)) {
                                    return 'Administrator';
                                }
                                return $data->dreamland_account_type === 'creator' ? 'Creator' : 'Viewer';
                            },
                        ],
                        [
                            'attribute' => 'status',
                            'value' => static function ($data) {
                                return $data->getStatus();
                            },
                        ],
                    ],
                    'tableOptions' => [
                        'id' => 'theDatatable',
                        'class' => 'table table-striped table-bordered table-hover',
                    ],
                    'toolbar' => [

                        [

                        ],
                        //'{export}',
                        //'{toggleData}'
                    ],
                    //'toggleDataContainer' => ['class' => 'btn-group-sm'],
                    //'exportContainer' => ['class' => 'btn-group-sm'],
                    'exportConfig' => [
                        GridView::CSV => ['label' => 'CSV'],
                        GridView::EXCEL => [],
                        // html settings],
                
                    ],


                    /*
                    'toolbar' =>  [
                        ['content'=>
                            Html::button('&lt;i class="glyphicon glyphicon-plus">&lt;/i>', ['type'=>'button', 'title'=>Yii::t('app', 'Add Book'), 'class'=>'btn btn-success', 'onclick'=>'alert("This will launch the book creation form.\n\nDisabled for this demo!");']) . ' '.
                            Html::a('&lt;i class="glyphicon glyphicon-repeat">&lt;/i>', ['grid-demo'], ['data-pjax'=>0, 'class' => 'btn btn-default', 'title'=>Yii::t('app', 'Reset Grid')])
                        ],
                    ],*/

                    'pjax' => false,
                    'bordered' => true,
                    'striped' => false,
                    'condensed' => false,
                    'responsive' => true,
                    'hover' => true,
                    'floatHeader' => false,
                    //'floatHeaderOptions' => ['top' => $scrollingTop],
                    'showPageSummary' => false,
                    'panel' => [
                        // 'type' => GridView::TYPE_PRIMARY
                    ],

                ]); ?>
            </div>


        </div>
        <!-- /.box -->



        <!-- /.col -->
    </div>
</div>
<div id="messagewinpopup" class="modal">
    <!-- popup -->
    <div class="modal-content">
        <div class="panel-body">

        <label class="control-label" for="competition-position">Fill the detail for broadcast notificattion detail</label>
                    <a class="close" href="#">&times;</a>
                    <div class="content">
                        <form id="formBroadcast" method="post">

                            <div class="copy_containerd">

                                <div class="form-group">
                                    <label class="control-label">Target audience</label>
                                    <select name="audience_type" id="audience_type" class="form-control">
                                        <?php foreach ($audienceLabels as $key => $label): ?>
                                            <option value="<?= Html::encode($key) ?>" <?= $audience === $key ? 'selected' : '' ?>>
                                                <?= Html::encode($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <hr class="featurette-divider">
                                    <label class="control-label">Title</label>
                                    <input type="text" class="form-control" required id="notification_title"  name="notification_title"  maxlength="50">
                                </div>
                                <div class="form-group">
                                    <label class="control-label">Body</label>
                                    <textarea name="notification_message" required class="form-control" rows="4"  maxlength="150"></textarea>
                                </div>
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit"> Send</button>
                                </div>
                            </div>

                            <input type="hidden" id="selectedRows" name="selectedRows"><br>

                        </form>

                    </div> 

        </div>
    </div>

</div>

<?php
$js = <<<JS
$(document).ready(function(){
  function openModal(selectedRows, audienceType) {
    if (!selectedRows && audienceType === 'custom') {
      alert('Please select at least one user');
      return false;
    }
    $('#audience_type').val(audienceType || '$audience');
    $('#selectedRows').val(selectedRows || '');
    $('.modal').show();
    return true;
  }

  $('#sendButton').click(function(){
    var keys = $('#grid').yiiGridView('getSelectedRows');
    openModal(keys.toString(), 'custom');
  });

  $('#sendGroupButton').click(function(e){
    e.preventDefault();
    openModal('', $(this).data('audience'));
  });

  $(".close").click(function(){
    $(".modal").hide();
  });
});
JS;
$this->registerJs($js, \yii\web\view::POS_READY);
?>