<?php
use yii\helpers\Html;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Notification</title>
  
<style>
        body {
            font-family: Arial, sans-serif;
            background-color:rgb(175, 175, 175);
            padding: 20px;
        }
        .container {
            background-color:rgb(228, 228, 228);
            padding: 40px;
            display: flex; 
            justify-content: center; 

        }
        .container_content {
            max-width: 600px;
            background-color:rgb(252, 252, 252);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
          
        }
        .otp {
            font-size: 20px;
            font-weight: bold;
            color: #d9534f;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 5px;
            display: inline-block;
            margin: 15px 0;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
    </style>

</head>
<body>
    <div class="container">
      
        <?= $content ?>  <!-- Email body will be injected here -->
      
    </div>
</body>
</html>