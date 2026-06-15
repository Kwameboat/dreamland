<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \common\models\LoginForm */

$this->title = 'Ticket Detail';

?>

<div class="login-box" style="width:600px">
   
    <!-- /.login-logo -->
    <div class="login-box-body">
        <?php if(!$model){
            
         echo '<h5>Ticket detail not found</h5>';   
        
        }else{ ?>
        <h4 class="login-box-msg">Ticket Detail</h4>
        
    

    <?php echo  DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute'  => 'status',
                'value'  => function ($data) {
                    return @$data->statusButton;
                },
                'format'=>'raw'
            ],
            [
                'attribute'  => 'event_id',
                'value' => function($model){
                    return @$model->event->name;
                },
                'format'=>'raw'
            ],
            [
                'attribute'  => 'user_id',
                'value' => function($model){
                    
                    return @$model->user->name;
                },
              
                'format'=>'raw'
            ],
            
            'created_at:datetime',
            [
                'attribute'  => 'event_ticket_id',
                'value'  => function ($data) {
                    return @$data->ticket_qty.' x '.@$data->ticket->ticket_type;
                },
                'format'=>'raw'
            ],
            'coupon',
            'coupon_discount_value',
            'paid_amount',
            'ticket_amount',
            [
                'label'  => 'Transactions',
                'attribute'  => 'Paymentd',
                'value'  => function ($data) {
                    //return $data->ticket_qty.' x '.$data->ticket->ticket_type;
                    if(!$data){
                        return '';
                    }
                    $str='';
                    foreach($data->payment as $payment){
                        $str.='$'.$payment->amount.' / '.$payment->paymentModeString .' / '.$payment->transaction_id;

                        $str.='<br>';
                    };
                    return $str;

                },
                'format'=>'raw'
            ],

          
            
        ],
    ]);
    
    
        }
    ?>


     

    </div>
    <!-- /.login-box-body -->
</div><!-- /.login-box -->