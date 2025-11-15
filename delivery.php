<?php
require "config.php";
require "local_time.php"; // $time variable
$pay_id = $_GET["pay_id"] ?? '';

if (empty($pay_id)) {
    die("No Payment ID provided.");
}

// We will store shortage messages here
$shortages = [];

// Start a transaction: This is CRITICAL.
// It ensures that if one item is out of stock, the other items are not
// accidentally deducted from the stock. All or nothing.
$con->begin_transaction();

try {
    // --- 1. Get Order Details Securely ---
    $stmt_order = $con->prepare("SELECT laptop, mobile, calculator FROM order_details WHERE payment_id = ?");
    $stmt_order->bind_param("s", $pay_id);
    $stmt_order->execute();
    $r1 = $stmt_order->get_result()->fetch_assoc();
    $stmt_order->close();

    if (!$r1) {
        throw new Exception("Order not found.");
    }

    $laptop_ordered = (int)$r1["laptop"];
    $mobile_ordered = (int)$r1["mobile"];
    $calculator_ordered = (int)$r1["calculator"];

    // --- 2. Get All Product Stock in One Go (More Efficient) ---
    $stmt_stock = $con->prepare("SELECT product_id, total_pieces FROM product_details WHERE product_id IN ('111', '222', '333') FOR UPDATE");
    $stmt_stock->execute();
    $stock_result = $stmt_stock->get_result();
    
    $stock_map = [
        '111' => 0, // Laptop
        '222' => 0, // Mobile
        '333' => 0  // Calculator
    ];
    
    while ($row = $stock_result->fetch_assoc()) {
        $stock_map[$row['product_id']] = (int)$row['total_pieces'];
    }
    $stmt_stock->close();

    // --- 3. Check for Shortages (Your Core Logic) ---
    if ($laptop_ordered > 0 && $stock_map['111'] < $laptop_ordered) {
        $shortages[] = [
            'name' => 'Laptop',
            'needed' => $laptop_ordered,
            'have' => $stock_map['111'],
            'short_by' => $laptop_ordered - $stock_map['111']
        ];
    }
    
    if ($mobile_ordered > 0 && $stock_map['222'] < $mobile_ordered) {
        $shortages[] = [
            'name' => 'Mobile',
            'needed' => $mobile_ordered,
            'have' => $stock_map['222'],
            'short_by' => $mobile_ordered - $stock_map['222']
        ];
    }

    if ($calculator_ordered > 0 && $stock_map['333'] < $calculator_ordered) {
        $shortages[] = [
            'name' => 'Calculator',
            'needed' => $calculator_ordered,
            'have' => $stock_map['333'],
            'short_by' => $calculator_ordered - $stock_map['333']
        ];
    }

    // --- 4. Decide What to Do ---
    if (!empty($shortages)) {
        // A shortage was found. Abort the transaction.
        $con->rollback();
        // Go to the HTML part below to display the errors.
        
    } else {
        // SUCCESS: No shortages. Update all stock and the order.
        
        if ($laptop_ordered > 0) {
            $stmt_update = $con->prepare("UPDATE product_details SET total_pieces = total_pieces - ? WHERE product_id = '111'");
            $stmt_update->bind_param("i", $laptop_ordered);
            $stmt_update->execute();
            $stmt_update->close();
        }
        if ($mobile_ordered > 0) {
            $stmt_update = $con->prepare("UPDATE product_details SET total_pieces = total_pieces - ? WHERE product_id = '222'");
            $stmt_update->bind_param("i", $mobile_ordered);
            $stmt_update->execute();
            $stmt_update->close();
        }
        if ($calculator_ordered > 0) {
            $stmt_update = $con->prepare("UPDATE product_details SET total_pieces = total_pieces - ? WHERE product_id = '333'");
            $stmt_update->bind_param("i", $calculator_ordered);
            $stmt_update->execute();
            $stmt_update->close();
        }

        // Finally, update the order status
        $stmt_status = $con->prepare("UPDATE order_details SET status = 'DELIVERED', delivery_time = ? WHERE payment_id = ?");
        $stmt_status->bind_param("ss", $time, $pay_id); // $time is from local_time.php
        $stmt_status->execute();
        $stmt_status->close();

        // All good. Save all changes and redirect.
        $con->commit();
        header("location:admin.php");
        exit;
    }

} catch (Exception $e) {
    // Something went wrong
    $con->rollback();
    die("An error occurred: " . $e->getMessage());
}

// --- HTML Part: This only shows if a shortage was found ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Shortage - Delivery Failed</title>
    <style>
        :root {
            --primary-color: #4a00e0;
            --secondary-color: #8e2de2;
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333;
            --text-light: #555;
            --red-color: #e74c3c;
            --red-light-bg: #fdeded;
            --red-light-border: #fbe2e2;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --gradient: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .container {
            width: 100%;
            max-width: 600px;
            text-align: center;
        }
        .main-alert-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 2.5rem;
            text-align: center;
            border-top: 5px solid var(--red-color);
        }
        .main-alert-card h1 {
            color: var(--red-color);
            font-size: 1.8rem;
            margin-top: 0;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .main-alert-card h1 svg {
            margin-right: 0.5rem;
            flex-shrink: 0;
        }
        .main-alert-card p {
            font-size: 1.1rem;
            color: var(--text-light);
            line-height: 1.6;
        }
        
        .shortage-list {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .shortage-item {
            background: var(--red-light-bg);
            border: 1px solid var(--red-light-border);
            color: #721c24;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: left;
        }
        .shortage-item h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.3rem;
        }
        .shortage-item p {
            margin: 0;
            font-size: 1rem;
            line-height: 1.5;
        }
        .shortage-item strong {
            font-size: 1.2rem;
        }

        .back-button {
            display: inline-block;
            margin-top: 2rem;
            background: var(--gradient);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .back-button:hover {
            box-shadow: 0 5px 15px rgba(74, 0, 224, 0.3);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-alert-card">
            <h1>
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </svg>
                Delivery Failed
            </h1>
            <p>Insufficient stock to fulfill this order. The delivery has been halted and no stock has been changed.</p>
            
            <div class="shortage-list">
                <?php foreach ($shortages as $item): ?>
                    <div class="shortage-item">
                        <h2><?php echo htmlspecialchars($item['name']); ?> Shortage</h2>
                        <p>
                            You need <strong><?php echo $item['needed']; ?></strong> pieces to deliver,
                            but you only have <strong><?php echo $item['have']; ?></strong> in stock.
                        </p>
                        <p style="margin-top: 5px; font-weight: 600;">
                            Please restock <strong><?php echo $item['short_by']; ?></strong> more pieces.
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <a href="admin.php" class="back-button">&larr; Back to Admin Dashboard</a>
        </div>
    </div>
</body>
</html>
