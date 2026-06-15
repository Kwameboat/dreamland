<?php
use yii\helpers\Html;
$this->title='Password Reset Request';
?>
    <div class="container_content">
        <h2>Password Reset Request</h2>
        <p>Hello <?= Html::encode($username) ?>,</p>
        <p>We have received a request to reset your password. If you made this request, please use the OTP code below to proceed:</p>
        <h3 style="color: red;"><?= Html::encode($otp) ?></h3>
        <p>If you did not request a password reset, please ignore this email.</p>
        <p class="footer">This is an automated message. Please do not reply.</p>
    </div>
