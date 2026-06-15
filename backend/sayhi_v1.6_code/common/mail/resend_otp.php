<?php
use yii\helpers\Html;
$this->title='Register';
?>
    <div class="container_content">
        <h2>OTP resend</h2>
        <p>Hello <?= Html::encode($username) ?>,</p>
        <p>We have received request for OTP. If you requested then use OTP code below:</p>
        <h3 style="color: red;"><?= Html::encode($otp) ?></h3>
        <p>If you did not requested, please ignore this email.</p>
        <p class="footer">This is an automated message. Please do not reply.</p>
    </div>
    