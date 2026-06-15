<?php
use yii\helpers\Html;
use yii\helpers\Url;

//print_r($postResult);

?>
<style>
    .event-image {
        width: 100%;
        max-height: 300px;
        object-fit: cover;
        border-radius: 5px;
    }

    .separator {
        display: flex;
        align-items: flex-start;
        margin-bottom: 25px;
        border-bottom: 1px solid #eee;
        padding-bottom: 25px;
    }

    .event-card-detail span {


        width: 50%;

    }
</style>

<div class="container mt-5">
   
<script src="https://js.stripe.com/v3/"></script>
    <style>
        /* Add some basic styling to the form */
        .form-container {
            max-width: 400px;
            margin: 0 auto;
        }
        input[type="text"], input[type="email"], input[type="number"] {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <form id="payment-form">
            <input type="text" id="name" placeholder="Name">
            <input type="email" id="email" placeholder="Email">
            <input type="number" id="amount" placeholder="Amount">
            <div id="card-element"><!--Stripe.js injects the Card Element--></div>
            <button type="submit">Pay Now</button>
        </form>
    </div>

    <script>
        // Initialize Stripe.js with your publishable key
        var stripe = Stripe('pk_test_51HyFNTAKWfZAkcs9vyUjXk3HNlZJGDyNrTlVYGsXG7IYB8v9F5RwpR8zwe0W1wPBRULl2LcNCZBdUh35q6yWsN0v002D5UQ5dU');

        // Create an instance of Elements
        var elements = stripe.elements();

        // Create an instance of the card Element
        var card = elements.create('card');

        // Add an instance of the card Element into the `card-element` div
        card.mount('#card-element');

        // Handle form submission
        var form = document.getElementById('payment-form');
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    // Inform the user if there was an error
                    alert(result.error.message);
                } else {
                    // Send the token to your server
                    
                    var token = result.token;
                    console.log('a');
                    console.log(token.id);
                    // You can now send this token to your backend for further processing
                    // For example:
                    // fetch('/process-payment', {
                    //     method: 'POST',
                    //     headers: {
                    //         'Content-Type': 'application/json',
                    //     },
                    //     body: JSON.stringify({token: token}),
                    // })
                    // .then(response => response.json())
                    // .then(data => {
                    //     console.log('Success:', data);
                    // })
                    // .catch((error) => {
                    //     console.error('Error:', error);
                    // });
                }
            });
        });
    </script>
</div>