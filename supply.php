<?php 
require "config.php";
require "admin_details.php";

$pay_id = $_GET["pay_id"] ?? ''; // Use null coalescing for safety

if (empty($pay_id)) {
    die("No Payment ID provided.");
}

// Start a transaction. This ensures all or none of the queries run.
$con->begin_transaction();

try {
    
    // 1. Get the order details securely
    // We also join payment_details to ensure this is a valid admin-to-supplier order
    $sql_get_order = "
        SELECT o.laptop, o.mobile, o.calculator
        FROM order_details o
        JOIN payment_details p ON o.payment_id = p.payment_id
        WHERE o.payment_id = ?
          AND p.sender_account = ?
          AND p.receiver_account = ?
          AND o.status = 'ADMIN ORDER'
    ";
    
    $stmt_get = $con->prepare($sql_get_order);
    $stmt_get->bind_param("sss", $pay_id, $admin_account_number, $supplier_account_number);
    $stmt_get->execute();
    $result = $stmt_get->get_result();

    if ($result->num_rows == 0) {
        // No such order found, or it's not a valid admin order.
        throw new Exception("Invalid or already processed order.");
    }
    
    $r1 = $result->fetch_assoc();
    $laptop = (int)$r1["laptop"];
    $mobile = (int)$r1["mobile"];
    $calculator = (int)$r1["calculator"];
    $stmt_get->close();

    // 2. Update product stock securely
    // We use `total_pieces = total_pieces + ?` to avoid race conditions.
    
    if ($laptop > 0) {
        $stmt_update = $con->prepare("UPDATE product_details SET total_pieces = total_pieces + ? WHERE product_id = '111'");
        $stmt_update->bind_param("i", $laptop);
        $stmt_update->execute();
        $stmt_update->close();
    }

    if ($mobile > 0) {
        $stmt_update = $con->prepare("UPDATE product_details SET total_pieces = total_pieces + ? WHERE product_id = '222'");
        $stmt_update->bind_param("i", $mobile);
        $stmt_update->execute();
        $stmt_update->close();
    }

    if ($calculator > 0) {
        $stmt_update = $con->prepare("UPDATE product_details SET total_pieces = total_pieces + ? WHERE product_id = '333'");
        $stmt_update->bind_param("i", $calculator);
        $stmt_update->execute();
        $stmt_update->close();
    }

    // 3. Delete the order records securely (as per your original logic)
    
    $stmt_del_order = $con->prepare("DELETE FROM order_details WHERE payment_id = ?");
    $stmt_del_order->bind_param("s", $pay_id);
    $stmt_del_order->execute();
    $stmt_del_order->close();

   // $stmt_del_payment = $con->prepare("DELETE FROM payment_details WHERE payment_id = ?");
   // $stmt_del_payment->bind_param("s", $pay_id);
   // $stmt_del_payment->execute();
   // $stmt_del_payment->close();

    // If all queries were successful, commit the transaction
    $con->commit();

    // Redirect back to the supplier page
    header("location:supplier.php");

} catch (Exception $e) {
    // If any query failed, roll back all changes
    $con->rollback();
    echo "Transaction failed: " . $e->getMessage();
}
?>
