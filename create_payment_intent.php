<?php
session_start();
require "config.php";
require "admin_details.php";
require "local_time.php";
require 'vendor/autoload.php';

// --- SECURITY CHECK 1: Is the user logged in? ---
if (!isset($_SESSION['email'])) {
    // If not logged in, stop everything and send an error
    http_response_code(403); // 403 Forbidden
    echo json_encode(['error' => 'You must be logged in to make a payment.']);
    exit;
}

// --- SECURITY CHECK 2: Is there a cart? ---
$cart = $_SESSION['cart_data'] ?? null;
if (!$cart) {
    http_response_code(400); // 400 Bad Request
    echo json_encode(['error' => 'No cart data found.']);
    exit;
}

// Your Stripe Secret Key 
\Stripe\Stripe::setApiKey('STRIPE_SECRET_KEY'); // PUT YOUR REAL KEY HERE

// Get User Data
$customer_email = $_SESSION['email']; // We know this exists now
$customer_name = $_SESSION['name'];

// --- DEFINE SENDER ACCOUNT FOR DATABASE ---
// Even if their internal bank account is "NOT SET", they can still pay via Stripe.
// We use a special string so you can identify these payments in your Admin Panel.
$sender_account = "Stripe | " . $customer_email; 

$total_price = $cart['total_price'];
$lpc = $cart['lpc'];
$mpc = $cart['mpc'];
$cpc = $cart['cpc'];
$addr = $cart['addr'];

// --- 1. Create the Order in your database with "PENDING" status ---
$payment_id = "stripe-" . mt_rand(10000, 99999);
$receiver = "stripe-" . $admin_account_number;
$status_for_order = "ORDER CONFIRMED";
$status_for_payment = "SUCCESSFUL";
$delivery_time = "TBA";

$con->begin_transaction();
try {
    $stmt_payment = $con->prepare("INSERT INTO payment_details (payment_id, sender_account, receiver_account, amount, payment_time, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_payment->bind_param("ssssss", $payment_id, $sender_account, $receiver, $total_price, $time, $status_for_payment);
    $stmt_payment->execute();

    $stmt_order = $con->prepare("INSERT INTO order_details (payment_id, laptop, mobile, calculator, payment_time, delivery_time, destination, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_order->bind_param("siisssss", $payment_id, $lpc, $mpc, $cpc, $time, $delivery_time, $addr, $status_for_order);
    $stmt_order->execute();
    
    $con->commit();
} catch (Exception $e) {
    $con->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// --- 2. Create the Payment Intent on Stripe ---
try {
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $total_price * 100, // Amount in cents/paisa
        'currency' => 'bdt', // or 'usd'
        'automatic_payment_methods' => ['enabled' => true],
        'description' => "Order from $customer_name",
        'metadata' => [
            'local_payment_id' => $payment_id, // Critical for the Webhook
            'customer_email' => $customer_email
        ]
    ]);

    echo json_encode(['clientSecret' => $paymentIntent->client_secret]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Stripe error: ' . $e->getMessage()]);
}
?>