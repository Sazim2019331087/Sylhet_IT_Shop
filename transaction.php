<?php 
require "config.php";
require "admin_details.php"; // We need this for $admin_account_number and $admin_secret

// Get all data from POST
$sender_account = $_POST["sender"] ?? '';
$receiver_account = $_POST["receiver"] ?? '';
$payment_time = $_POST["time"] ?? '';
$amount = (int)($_POST["amount"] ?? 0);
$secret_pin = $_POST["secret_pin"] ?? '';

$lpc = (int)($_POST["lpc"] ?? 0);
$mpc = (int)($_POST["mpc"] ?? 0);
$cpc = (int)($_POST["cpc"] ?? 0);
$addr = $_POST["addr"] ?? '';

// --- Initial Input Validation ---
if (empty($secret_pin)) {
    echo json_encode(["status" => "Pin is empty. Payment failed.", "color" => "red"]);
    exit;
}
if ($amount <= 0) {
    echo json_encode(["status" => "0 BDT cannot be paid. Payment failed.", "color" => "red"]);
    exit;
}

// Start a transaction.
$con->begin_transaction();

try {
    
    $current_balance = 0;
    $actual_secret = null;

    // --- 1. Get Sender's Balance and Secret ---
    // ** THIS IS THE NEW LOGIC THAT HANDLES BOTH ADMIN AND CUSTOMERS **
    
    if ($sender_account === $admin_account_number) {
        // --- SENDER IS ADMIN ---
        // Get balance from bank_details
        $stmt1 = $con->prepare("SELECT current_balance FROM bank_details WHERE account_number = ?");
        $stmt1->bind_param("s", $sender_account);
        $stmt1->execute();
        $r1 = $stmt1->get_result()->fetch_assoc();
        $stmt1->close();
        
        if (!$r1) {
            throw new Exception("Admin bank account not found.");
        }
        
        $current_balance = (int)$r1["current_balance"];
        $actual_secret = $admin_secret; // Get secret from admin_details.php
        
    } else {
        // --- SENDER IS A CUSTOMER ---
        // Get balance from bank_details AND secret from customer_details
        $stmt1 = $con->prepare("
            SELECT 
                b.current_balance, 
                c.secret 
            FROM 
                bank_details b
            JOIN 
                customer_details c ON b.account_number = c.account_number
            WHERE 
                b.account_number = ?
        ");
        $stmt1->bind_param("s", $sender_account);
        $stmt1->execute();
        $r1 = $stmt1->get_result()->fetch_assoc();
        $stmt1->close();

        if (!$r1) {
            throw new Exception("Customer account details not found.");
        }
        
        $current_balance = (int)$r1["current_balance"];
        $actual_secret = $r1["secret"];
    }

    // --- 2. Validate Secret and Balance ---
    if ($actual_secret !== $secret_pin) {
        throw new Exception("Pin is incorrect. Payment failed.");
    }
    if ($current_balance < $amount) {
        throw new Exception("Low Balance. Payment failed.");
    }

    // --- 3. Debit the sender ---
    $stmt2 = $con->prepare("UPDATE bank_details SET current_balance = current_balance - ? WHERE account_number = ?");
    $stmt2->bind_param("is", $amount, $sender_account);
    $stmt2->execute();
    $stmt2->close();

    // --- 4. Credit the receiver ---
    $stmt3 = $con->prepare("UPDATE bank_details SET current_balance = current_balance + ? WHERE account_number = ?");
    $stmt3->bind_param("is", $amount, $receiver_account);
    $stmt3->execute();
    $stmt3->close();
    
    // --- 5. Insert into payment_details ---
    $pay_id = mt_rand(10000, 99999); // Use a prefix for clarity
    $payment_status = "SUCCESSFUL";
    
    $stmt4 = $con->prepare("INSERT INTO payment_details (payment_id, sender_account, receiver_account, amount, payment_time, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt4->bind_param("ssssss", $pay_id, $sender_account, $receiver_account, $amount, $payment_time, $payment_status);
    $stmt4->execute();
    $stmt4->close();
    
    $stmt5 = $con->prepare("INSERT INTO bank_payment_details (payment_id, sender_account, receiver_account, amount, payment_time, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt5->bind_param("ssssss", $pay_id, $sender_account, $receiver_account, $amount, $payment_time, $payment_status);
    $stmt5->execute();
    $stmt5->close();
    

    // --- 6. Insert into order_details ---
    $status = "ORDER CONFIRMED";
    // Check if the sender is the admin to set the correct order status
    if ($sender_account === $admin_account_number) {
        $status = "ADMIN ORDER";
    }

    $stmt5 = $con->prepare("INSERT INTO order_details (payment_id, laptop, mobile, calculator, payment_time, delivery_time, destination, status) VALUES (?, ?, ?, ?, ?, 'TBA', ?, ?)");
    $stmt5->bind_param("siissss", $pay_id, $lpc, $mpc, $cpc, $payment_time, $addr, $status);
    $stmt5->execute();
    $stmt5->close();

    // --- If all queries were successful, commit the transaction ---
    $con->commit();
    echo json_encode(["status" => "Payment Successful!", "color" => "green"]);

} catch (Exception $e) {
    // --- If any query failed, roll back all changes ---
    $con->rollback();
    echo json_encode(["status" => $e->getMessage(), "color" => "red"]);
}

$con->close();
?>
