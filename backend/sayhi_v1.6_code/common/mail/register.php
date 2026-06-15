<?php
use yii\helpers\Html;
$this->title='Register';
?>
    <div class="container_content">
        <h2>Welcome to Our Platform!</h2>
        <p>Hello <?= Html::encode($username) ?>,</p>
        <p>Thank you for registering with us! To complete your registration, please verify your email using the OTP code below:</p>
        <h3 style="color: red;"><?= Html::encode($otp) ?></h3>
        <p>If you did not request a password reset, please ignore this email.</p>
        <p class="footer">This is an automated message. Please do not reply.</p>
    </div>
