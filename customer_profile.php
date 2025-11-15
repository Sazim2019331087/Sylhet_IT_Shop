<?php
require "config.php";
require "local_time.php";
session_start();

// Redirect if not logged in
if (!isset($_SESSION["email"])) {
    header("Location: customer_login.php"); // Assuming you have a login page
    exit();
}

$email = $_SESSION["email"];
$name = $_SESSION["name"];
// $password = $_SESSION["password"]; // Removed: Don't store plain text password in session
$account_number = $_SESSION["account_number"];
$secret = $_SESSION["secret"];

// --- Helper function to sanitize output ---
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// --- Fetch Statistics (Using Prepared Statements) ---
$total_spent = 0;
$total_orders = 0;
$favorite_item = "N/A";

if ($account_number !== "NOT SET") {
    // 1. Get Total Spent
    $stmt_spent = $con->prepare("SELECT SUM(amount) as total_spent FROM payment_details WHERE sender_account = ?");
    $stmt_spent->bind_param("s", $account_number);
    $stmt_spent->execute();
    $r_spent = $stmt_spent->get_result()->fetch_assoc();
    $total_spent = $r_spent['total_spent'] ?? 0;
    $stmt_spent->close();

    // 2. Get Total Orders & Favorite Item
    $stmt_items = $con->prepare("
        SELECT 
            COUNT(o.payment_id) as order_count,
            SUM(o.laptop) as laptop_total, 
            SUM(o.mobile) as mobile_total, 
            SUM(o.calculator) as calculator_total
        FROM order_details o
        JOIN payment_details p ON o.payment_id = p.payment_id
        WHERE p.sender_account = ?
    ");
    $stmt_items->bind_param("s", $account_number);
    $stmt_items->execute();
    $r_items = $stmt_items->get_result()->fetch_assoc();
    $total_orders = $r_items['order_count'] ?? 0;

    $item_counts = [
        "Laptop" => $r_items['laptop_total'] ?? 0,
        "Mobile" => $r_items['mobile_total'] ?? 0,
        "Calculator" => $r_items['calculator_total'] ?? 0,
    ];
    arsort($item_counts);
    if (current($item_counts) > 0) {
        $favorite_item = key($item_counts);
    }
    $stmt_items->close();
}

/**
 * Helper function to build the product details string from a row.
 */
function get_product_details($row) {
    $details = "";
    if ($row["laptop"] > 0) {
        $details .= "<span class='product-item'>Laptop: <b>" . e($row["laptop"]) . "</b></span>";
    }
    if ($row["mobile"] > 0) {
        $details .= "<span class='product-item'>Mobile: <b>" . e($row["mobile"]) . "</b></span>";
    }
    if ($row["calculator"] > 0) {
        $details .= "<span class='product-item'>Calculator: <b>" . e($row["calculator"]) . "</b></span>";
    }
    return $details;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Sylhet IT Shop</title>
    <script src="js/jquery.min.js"></script>
    <style>
        :root {
            --primary-color: #4a00e0;
            --secondary-color: #8e2de2;
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333;
            --text-light: #555;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            --gradient: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            --gradient-light: linear-gradient(135deg, #f5f7fa, #c3cfe2);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            color: var(--text-color);
        }

        /* --- Header --- */
        .dashboard-header {
            background: var(--gradient);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .dashboard-header h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        .header-buttons {
            display: flex;
            gap: 1rem;
        }
        .logout-btn {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1rem;
            cursor: pointer;
        }
        .logout-btn:hover {
            background-color: white;
            color: var(--primary-color);
        }

        /* --- Main Dashboard Layout --- */
        .dashboard-container {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 30px;
            padding: 30px;
            max-width: 1600px;
            margin: 0 auto;
        }

        .dashboard-sidebar, .dashboard-main {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* --- Reusable Card --- */
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }
        .card-header.gradient {
            background: var(--gradient-light);
        }
        .card-header h2 {
            margin: 0;
            font-size: 1.3rem;
        }
        .card-body {
            padding: 25px;
            font-size: 1rem;
            line-height: 1.6;
        }

        /* --- Profile Card --- */
        .profile-card-body p {
            margin: 0 0 15px 0;
            color: var(--text-light);
        }
        .profile-card-body p strong {
            display: block;
            color: var(--text-color);
            font-size: 1.1rem;
            margin-bottom: 3px;
        }
        #account_number_text {
            color: var(--primary-color);
            font-weight: 600;
        }

        /* --- Stats Card --- */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .stat-item {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .stat-item span {
            display: block;
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 5px;
        }
        .stat-item strong {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        .stat-item.full-width {
            grid-column: 1 / -1;
        }
        
        /* --- Payment Settings Card --- */
        .shop-link-btn {
            display: inline-block;
            background: var(--gradient);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .shop-link-btn:hover {
            box-shadow: 0 5px 15px rgba(74, 0, 224, 0.3);
            transform: translateY(-2px);
        }

        /* --- Order Cards --- */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .order-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            background: var(--gradient-light);
            border-radius: 12px 12px 0 0;
        }
        .order-header-left span {
            display: block;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        .order-header-left strong {
            font-size: 1.1rem;
        }
        .order-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .order-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 25px;
        }
        .order-body-section h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: var(--text-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 5px;
            display: inline-block;
        }
        .product-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .product-item {
            display: block;
            background: #f9f9f9;
            padding: 8px 12px;
            border-radius: 6px;
        }
        .order-details-list span {
            display: block;
            color: var(--text-light);
            margin-bottom: 8px;
        }
        .order-details-list span strong {
            color: var(--text-color);
        }

        /* --- Responsive Design --- */
        @media (max-width: 1200px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                gap: 15px;
                padding: 20px;
            }
            .dashboard-container {
                padding: 15px;
                gap: 20px;
            }
            .dashboard-main {
                gap: 20px;
            }
            .card-header, .card-body {
                padding: 20px;
            }
            .order-body {
                grid-template-columns: 1fr;
            }
        }

    </style>
        <link rel="icon" href="shop_icon.png" type="image/x-icon">

</head>
<body>

    <header class="dashboard-header">
        <h1>Sylhet IT Shop</h1>
        <div class="header-buttons">
            <a href="market.php" class="logout-btn">Shop</a>
            <a href="customer_logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="dashboard-container">
        
        <aside class="dashboard-sidebar">
            
            <div class="card">
                <div class="card-header gradient">
                    <h2>Welcome, <?php echo e($name); ?>!</h2>
                </div>
                <div class="card-body profile-card-body">
                    <p>
                        <strong>Name:</strong>
                        <?php echo e($name); ?>
                    </p>
                    <p>
                        <strong>Email:</strong>
                        <?php echo e($email); ?>
                    </p>
                    <p>
                        <strong>Bank Account:</strong>
                        <span id="account_number_text"><?php echo e($account_number); ?></span>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Quick Stats</h2>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item full-width">
                            <span>Total Spent</span>
                            <strong><?php echo number_format($total_spent, 2); ?> BDT</strong>
                        </div>
                        <div class="stat-item">
                            <span>Total Orders</span>
                            <strong><?php echo $total_orders; ?></strong>
                        </div>
                        <div class="stat-item">
                            <span>Favorite Item</span>
                            <strong><?php echo e($favorite_item); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

        </aside>

        <main class="dashboard-main">

            <div id="update_account_number"></div> 
            
            <?php if ($account_number === "NOT SET"): ?>
                <div class="card">
                    <div class="card-header gradient">
                        <h2>Setup Your Payment Account</h2>
                    </div>
                    <div class="card-body" style="text-align: center; padding: 25px;">
                        <p style="font-size: 1.1rem; color: var(--text-light); margin-top: 0;">
                            You must link your bank account to start shopping.
                        </p>
                        <a href="update_bank_info.php" class="shop-link-btn">Setup Payment Info</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header gradient">
                        <h2>Payment Settings</h2>
                    </div>
                    <div class="card-body" style="text-align: center; padding: 25px;">
                        <p style="font-size: 1.1rem; color: var(--text-light); margin-top: 0;">Your payment information is on file.</p>
                        <a href="update_bank_info.php" class="shop-link-btn">Update Payment Info</a>
                    </div>
                </div>
            <?php endif; ?>
            <div class="orders-section">
                <h2>My Current Orders</h2>
                <div class="orders-list" id="current-orders-list">
                    <?php
                    $sql_current = "
                        SELECT o.*, p.amount 
                        FROM order_details o
                        JOIN payment_details p ON o.payment_id = p.payment_id
                        WHERE p.sender_account = ? AND o.status = 'ORDER CONFIRMED'
                        ORDER BY STR_TO_DATE(
                            REPLACE(REPLACE(REPLACE(REPLACE(o.payment_time, 'st', ''), 'nd', ''), 'rd', ''), 'th', ''),
                            '%h:%i:%s %p %d %M , %Y %W'
                        ) DESC
                    ";
                    $stmt_current = $con->prepare($sql_current);
                    $stmt_current->bind_param("s", $account_number);
                    $stmt_current->execute();
                    $q_a = $stmt_current->get_result();
                    $t_1 = $q_a->num_rows;

                    if ($t_1 > 0) {
                        while($r_a = $q_a->fetch_assoc()) {
                            $product_details = get_product_details($r_a);
                            echo "
                            <div class='order-card'>
                                <div class='order-header'>
                                    <div class='order-header-left'>
                                        <span>Payment ID</span>
                                        <strong>" . e($r_a["payment_id"]) . "</strong>
                                    </div>
                                    <span class='order-amount'>" . number_format($r_a["amount"], 2) . " BDT</span>
                                </div>
                                <div class='order-body'>
                                    <div class='order-body-section'>
                                        <h4>Product Details</h4>
                                        <div class='product-list'>{$product_details}</div>
                                    </div>
                                    <div class='order-body-section'>
                                        <h4>Delivery Details</h4>
                                        <div class='order-details-list'>
                                            <span><strong>Status:</strong> PENDING DELIVERY</span>
                                            <span><strong>Paid At:</strong> " . e($r_a["payment_time"]) . "</span>
                                            <span><strong>Destination:</strong> " . e($r_a["destination"]) . "</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            ";
                        }
                    } else {
                        echo "<div class='card'><div class='card-body'><p>You have no current orders.</p></div></div>";
                    }
                    $stmt_current->close();
                    ?>
                </div>
            </div>

            <div class="orders-section">
                <h2>My Past Orders</h2>
                <div class="orders-list" id="past-orders-list">
                    <?php
                    $sql_past = "
                        SELECT o.*, p.amount 
                        FROM order_details o
                        JOIN payment_details p ON o.payment_id = p.payment_id
                        WHERE p.sender_account = ? AND o.status = 'DELIVERED'
                        ORDER BY STR_TO_DATE(
                            REPLACE(REPLACE(REPLACE(REPLACE(o.delivery_time, 'st', ''), 'nd', ''), 'rd', ''), 'th', ''),
                            '%h:%i:%s %p %d %M , %Y %W'
                        ) DESC
                    ";
                    $stmt_past = $con->prepare($sql_past);
                    $stmt_past->bind_param("s", $account_number);
                    $stmt_past->execute();
                    $q_c = $stmt_past->get_result();
                    $t_2 = $q_c->num_rows;

                    if ($t_2 > 0) {
                        while($r_c = $q_c->fetch_assoc()) {
                            $product_details2 = get_product_details($r_c);
                            echo "
                            <div class='order-card'>
                                <div class='order-header'>
                                    <div class='order-header-left'>
                                        <span>Payment ID</span>
                                        <strong>" . e($r_c["payment_id"]) . "</strong>
                                    </div>
                                    <span class='order-amount'>" . number_format($r_c["amount"], 2) . " BDT</span>
                                </div>
                                <div class='order-body'>
                                    <div class='order-body-section'>
                                        <h4>Product Details</h4>
                                        <div class='product-list'>{$product_details2}</div>
                                    </div>
                                    <div class='order-body-section'>
                                        <h4>Delivery Details</h4>
                                        <div class='order-details-list'>
                                            <span><strong>Status:</strong> <span style='color: #008744; font-weight: 600;'>DELIVERED</span></span>
                                            <span><strong>Paid At:</strong> " . e($r_c["payment_time"]) . "</span>
                                            <span><strong>Delivered At:</strong> " . e($r_c["delivery_time"]) . "</span>
                                            <span><strong>Destination:</strong> " . e($r_c["destination"]) . "</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            ";
                        }
                    } else {
                        echo "<div class='card'><div class='card-body'><p>You have no past orders.</p></div></div>";
                    }
                    $stmt_past->close();
                    ?>
                </div>
            </div>

        </main>
    </div>

    <script>
        $(document).ready(function(){
            
            // --- Auto-refresh orders ---
            setInterval(function(){
                $("#current-orders-list").load(window.location.href + " #current-orders-list > *");
            }, 5000);
            
            setInterval(function(){
                $("#past-orders-list").load(window.location.href + " #past-orders-list > *");
            }, 5000);

            // --- THIS SCRIPT IS NO LONGER NEEDED ---
            // The old, insecure form has been removed.
            
            // $("#button_set_account_number").click(function(){
            //     ...
            // });

        });
    </script>
    
</body>
</html>
