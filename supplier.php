<?php
require "config.php";
require "local_time.php";
require "admin_details.php"; // Contains $admin_account_number, $supplier_account_number, etc.

// --- 1. Get Pending Supply Orders (Secure & Performant) ---
//
// ** THE FIX IS HERE: We are now querying for `o.status = 'ADMIN ORDER'` **
//
$sql_orders = "
    SELECT
        p.payment_id, p.amount,
        o.laptop, o.mobile, o.calculator, o.payment_time, o.destination
    FROM
        payment_details p
    JOIN
        order_details o ON p.payment_id = o.payment_id
    WHERE
        p.sender_account = ?
        AND p.receiver_account = ?
        AND o.status = 'ADMIN ORDER' 
    ORDER BY STR_TO_DATE(
        REPLACE(REPLACE(REPLACE(REPLACE(o.payment_time, 'st', ''), 'nd', ''), 'rd', ''), 'th', ''),
        '%h:%i:%s %p %d %M , %Y %W'
    ) DESC
";

$stmt_orders = $con->prepare($sql_orders);
if (!$stmt_orders) {
    die("SQL Error: " . $con->error); // Error checking
}
$stmt_orders->bind_param("ss", $admin_account_number, $supplier_account_number);
$stmt_orders->execute();
$q1 = $stmt_orders->get_result();

$pending_orders_list = [];
$total_revenue = 0;
$item_demand = ['Laptop' => 0, 'Mobile' => 0, 'Calculator' => 0];

while ($r1 = $q1->fetch_assoc()) {
    $product_details = "";
    if ($r1["laptop"] > 0) {
        $product_details .= "Laptop: <b>" . $r1["laptop"] . "</b><br>";
        $item_demand['Laptop'] += (int)$r1["laptop"];
    }
    if ($r1["mobile"] > 0) {
        $product_details .= "Mobile: <b>" . $r1["mobile"] . "</b><br>";
        $item_demand['Mobile'] += (int)$r1["mobile"];
    }
    if ($r1["calculator"] > 0) {
        $product_details .= "Calculator: <b>" . $r1["calculator"] . "</b><br>";
        $item_demand['Calculator'] += (int)$r1["calculator"];
    }

    $r1['product_details_html'] = $product_details; // Add the generated HTML to the array
    $pending_orders_list[] = $r1; // Add the whole row to the list
    $total_revenue += (float)$r1['amount'];
}
$stmt_orders->close();

// --- 2. Process KPIs ---
$kpi_pending_orders = count($pending_orders_list);
$kpi_pending_revenue = $total_revenue;

$kpi_most_in_demand = 'N/A';
arsort($item_demand); // Sort items by demand, high to low
if (current($item_demand) > 0) {
    $kpi_most_in_demand = key($item_demand); // Get the name of the top item
}

// Helper function to format currency
function formatCurrency($value) {
    return 'BDT ' . number_format($value, 2);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="js/jquery.min.js"></script>
    <title>Supplier Dashboard - Sylhet IT Shop</title>
    <style>
        :root {
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --primary-color: #007bff; /* Blue for B2B professional look */
            --secondary-color: #17a2b8; /* Cyan */
            --text-color: #333;
            --text-light: #555;
            --green-color: #2ecc71;
            --red-color: #e74c3c;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            --gradient: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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
        .logout-btn {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .logout-btn:hover {
            background-color: white;
            color: var(--primary-color);
        }

        /* --- Main Content --- */
        .dashboard-container {
            padding: 30px;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        /* --- KPI Cards --- */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .kpi-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border-left: 5px solid var(--primary-color);
        }
        .kpi-card h3 {
            margin: 0 0 10px 0;
            font-size: 1rem;
            color: var(--text-light);
            text-transform: uppercase;
        }
        .kpi-card .value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-color);
        }
        .kpi-card.alert {
            border-left-color: var(--red-color);
        }
        .kpi-card.alert .value {
            color: var(--red-color);
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
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
        }
        .card-header h2 {
            margin: 0;
            font-size: 1.3rem;
        }
        .card-body {
            padding: 25px;
        }

        /* --- Table Styling --- */
        .table-wrapper {
            overflow-x: auto;
        }
        .styled-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        .styled-table th,
        .styled-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .styled-table th {
            background-color: #f8f9fa;
            color: var(--text-light);
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .styled-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .styled-table .product-details {
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-supply {
            background-color: var(--green-color);
            color: white;
        }
        .btn-supply:hover {
            background-color: #27ae60;
        }
        
        /* --- Responsive --- */
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                gap: 15px;
                padding: 20px;
            }
            .dashboard-container {
                padding: 15px;
            }
            .kpi-grid {
                gap: 15px;
            }
            .card-header, .card-body {
                padding: 20px;
            }
        }
        
    </style>
        <link rel="icon" href="shop_icon.png" type="image/x-icon">

</head>

<body>
    
    <header class="dashboard-header">
        <h1>Supplier Dashboard</h1>
        <a href="index.html" class="logout-btn">Logout</a>
    </header>

    <div class="dashboard-container">
        
        <div class="kpi-grid">
            <div class="kpi-card <?php echo ($kpi_pending_orders > 0) ? 'alert' : ''; ?>">
                <h3>Pending Supply Orders</h3>
                <div class="value" id="kpi-pending-orders"><?php echo $kpi_pending_orders; ?></div>
            </div>
            <div class="kpi-card">
                <h3>Pending Revenue</h3>
                <div class="value" id="kpi-pending-revenue"><?php echo formatCurrency($kpi_pending_revenue); ?></div>
            </div>
            <div class="kpi-card">
                <h3>Most In-Demand Item</h3>
                <div class="value" id="kpi-most-in-demand"><?php echo htmlspecialchars($kpi_most_in_demand); ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Pending Supply Orders</h2>
            </div>
            <div class="card-body" id="orders-card-body">
                <div class="table-wrapper">
                    <table class="styled-table" id="dataset">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Customer (Retailer)</th>
                                <th>Order Amount</th>
                                <th>Order Time</th>
                                <th>Products</th>
                                <th>Ship To</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="orders_table_body">
                            <?php
                            if ($kpi_pending_orders > 0) {
                                foreach ($pending_orders_list as $order) {
                                    echo "
                                    <tr>
                                        <td>" . htmlspecialchars($order['payment_id']) . "</td>
                                        <td>" . htmlspecialchars($admin_name) . "<br><small>" . htmlspecialchars($admin_email) . "</small></td>
                                        <td><strong>" . formatCurrency($order['amount']) . "</strong></td>
                                        <td>" . htmlspecialchars($order['payment_time']) . "</td>
                                        <td class='product-details'>" . $order['product_details_html'] . "</td>
                                        <td>" . htmlspecialchars($order['destination']) . "</td>
                                        <td><a href='supply.php?pay_id=" . htmlspecialchars($order['payment_id']) . "' class='btn btn-supply'>Supply</a></td>
                                    </tr>
                                    ";
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center; padding: 20px;'>No pending supply orders found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script>
        $(document).ready(function() {
            // This script reloads the main content (KPIs and Table)
            // every 2 seconds. It's much safer than 500ms.
            setInterval(function() {
                // We reload the *entire* container to update KPIs and the table
                $(".dashboard-container").load(window.location.href + " .dashboard-container > *");
            }, 2000); // 2 seconds
        });
    </script>
</body>
</html>
