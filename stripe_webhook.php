<?php
// This file is for Stripe to call, not for users.
require 'vendor/autoload.php';
require 'config.php';

// Your Stripe Webhook Secret (get this from your Stripe Dashboard)
//$webhook_secret = 'whsec_3b8bf04640014b880697fc7442efef244dab3d4d17430efd142d71b0cfde848c';
$webhook_secret = 'whsec_2eqLZpWHv5CD0JD3zVZtB0Al2vnoKP0g';

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $webhook_secret
    );
} catch(\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

// Handle the event
if ($event->type == 'payment_intent.succeeded') {
    $paymentIntent = $event->data->object; // contains the payment intent
    
    // ** THIS IS THE KEY **
    // Get the local 'payment_id' we stored in the metadata
    $local_payment_id = $paymentIntent->metadata->local_payment_id;
    
    if ($local_payment_id) {
        // We found our order! Now we update the database.
        
        // 1. Update payment_details
        $stmt_payment = $con->prepare("UPDATE payment_details SET status = 'SUCCESSFUL' WHERE payment_id = ? AND status = 'PENDING'");
        $stmt_payment->bind_param("s", $local_payment_id);
        $stmt_payment->execute();

        // 2. Update order_details
        $stmt_order = $con->prepare("UPDATE order_details SET status = 'ORDER CONFIRMED' WHERE payment_id = ? AND status = 'PENDING'");
        $stmt_order->bind_param("s", $local_payment_id);
        $stmt_order->execute();
    }
}

// Send a 200 OK response to Stripe to let them know we got it
http_response_code(200);
echo "Event received";
?>