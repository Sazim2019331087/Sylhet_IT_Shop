<?php
require "config.php";

if (isset($_GET['json_data'])) {
    
    header('Content-Type: application/json');

    $response = [
        'kpi' => [ 'pending_orders' => 0, 'pending_revenue' => 0, 'stock_alerts' => 0 ],
        'orders' => [],
        'stock' => [],
        'chart_data' => [ 'labels' => [], 'stock' => [], 'demand' => [], 'shortage' => [] ],
    ];

    // --- 1. Get Orders (Robust Logic) ---
    // FIX: Used TRIM() in SQL to ensure spaces don't break the email match.
    // We match if account matches OR if we can extract the email from "Stripe | email"
    $sql_orders = "
        SELECT 
            o.payment_id, o.laptop, o.mobile, o.calculator, o.payment_time, o.destination, o.status as order_status,
            p.amount, p.sender_account,
            c.name as customer_name, c.email as customer_email, c.account_number
        FROM order_details o
        JOIN payment_details p ON o.payment_id = p.payment_id
        LEFT JOIN customer_details c ON (
            p.sender_account = c.account_number 
            OR 
            (p.sender_account LIKE 'Stripe | %' AND c.email = TRIM(SUBSTRING(p.sender_account, 10)))
        )
        WHERE o.status = 'ORDER CONFIRMED'
        ORDER BY STR_TO_DATE(
            REPLACE(REPLACE(REPLACE(REPLACE(o.payment_time, 'st', ''), 'nd', ''), 'rd', ''), 'th', ''),
            '%h:%i:%s %p %d %M , %Y %W'
        ) DESC
    ";

    $stmt_orders = $con->prepare($sql_orders);
    $stmt_orders->execute();
    $q1 = $stmt_orders->get_result();
    
    $pending_count = 0;
    $laptop_in_demand = 0;
    $mobile_in_demand = 0;
    $calculator_in_demand = 0;

    while ($r1 = $q1->fetch_assoc()) {
        // Product Details Logic...
        $product_details = "";
        if ($r1["laptop"] > 0) {
            $product_details .= "Laptop: <b>" . $r1["laptop"] . "</b><br>";
            $laptop_in_demand += (int)$r1["laptop"];
        }
        if ($r1["mobile"] > 0) {
            $product_details .= "Mobile: <b>" . $r1["mobile"] . "</b><br>";
            $mobile_in_demand += (int)$r1["mobile"];
        }
        if ($r1["calculator"] > 0) {
            $product_details .= "Calculator: <b>" . $r1["calculator"] . "</b><br>";
            $calculator_in_demand += (int)$r1["calculator"];
        }

        // --- Customer Details Logic ---
        $cust_name = $r1['customer_name'];
        $cust_email = $r1['customer_email'];
        $cust_acc = $r1['account_number']; 

        // If the JOIN worked, $cust_name will NOT be empty.
        // If $cust_name IS empty, it means the JOIN failed.
        if (empty($cust_name)) {
            // 1. Check for Stripe format
            if (strpos($r1['sender_account'], 'Stripe |') !== false) {
                $cust_name = "Stripe Payment";
                $cust_email = str_replace("Stripe | ", "", $r1['sender_account']);
                $cust_acc = "<span class='badge-stripe'>Card</span>";
            } 
            // 2. Check for bKash format
            elseif (strpos($r1['sender_account'], 'bkash-') !== false) {
                $cust_name = "bKash Manual";
                $cust_email = "N/A";
                $cust_acc = $r1['sender_account'];
            } 
            // 3. Fallback for OLD or BROKEN data
            else {
                // [DEBUG]: Show exactly what is in the DB so we can fix it
                $cust_name = "Unknown (Fix Data)"; 
                $cust_email = "Raw: " . $r1['sender_account']; // Shows the raw DB value
                $cust_acc = "Unknown";
            }
        } elseif (empty($cust_acc)) {
            // If name found but account is empty, it was a Stripe/bKash match
            if (strpos($r1['sender_account'], 'Stripe |') !== false) {
                $cust_acc = "<span class='badge-stripe'>Card</span>";
            }
        }

        // Status Badge Logic...
        $status_badge = "";
        if($r1['order_status'] == 'ORDER CONFIRMED') {
            $status_badge = "<span style='color:green; font-weight:bold;'>CONFIRMED</span>";
            $pending_count++; 
            $response['kpi']['pending_revenue'] += (float)$r1['amount'];
        } elseif ($r1['order_status'] == 'PENDING') {
            $status_badge = "<span style='color:orange; font-weight:bold;'>PAYMENT PENDING</span>";
        }

        $response['orders'][] = [
            'pay_id' => $r1['payment_id'],
            'customer_name' => $r1['customer_name'],
            'customer_email' => $r1['customer_email'],
            'account_number' => $r1['account_number'],
            'total_amount' => $r1['amount'],
            'payment_time' => $r1['payment_time'],
            'product_details' => $product_details,
            'destination' => $r1['destination'],
            'status_display' => $status_badge,
            'raw_status' => $r1['order_status']
        ];
    }
    // ... (Rest of file remains the same) ...
    $response['kpi']['pending_orders'] = $pending_count;
    $stmt_orders->close();
    
    // ... Copy the stock logic from previous versions ...
    $demand_map = [ '111' => $laptop_in_demand, '222' => $mobile_in_demand, '333' => $calculator_in_demand ];
    $stock_alerts = 0;
    $sql_stock = "SELECT product_id, name, total_pieces FROM product_details WHERE product_id IN ('111', '222', '333') ORDER BY product_id";
    $stmt_stock = $con->prepare($sql_stock);
    $stmt_stock->execute();
    $q_stock = $stmt_stock->get_result();
    while ($r_stock = $q_stock->fetch_assoc()) {
        $pid = $r_stock['product_id'];
        $name = $r_stock['name'];
        $stock = (int)$r_stock['total_pieces'];
        $demand = $demand_map[$pid] ?? 0;
        $shortage = 0;
        $excess = 0;
        if ($stock < $demand) { $shortage = $demand - $stock; $stock_alerts++; } else { $excess = $stock - $demand; }
        $safety_stock = ceil($demand * 0.20);
        $recommended_restock = $shortage + $safety_stock;
        $response['stock'][] = [ 'pid' => $pid, 'name' => $name, 'demand' => $demand, 'stock' => $stock, 'shortage' => $shortage, 'excess' => $excess, 'recommend_restock' => $recommended_restock ];
        $response['chart_data']['labels'][] = $name;
        $response['chart_data']['stock'][] = $stock;
        $response['chart_data']['demand'][] = $demand;
        $response['chart_data']['shortage'][] = $shortage;
    }
    $stmt_stock->close();
    $response['kpi']['stock_alerts'] = $stock_alerts;
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <title>Admin Dashboard - Sylhet IT Shop</title>
    <style>
        :root {
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --primary-color: #4a00e0;
            --secondary-color: #8e2de2;
            --text-color: #333;
            --text-light: #555;
            --green-color: #2ecc71;
            --red-color: #e74c3c;
            --orange-color: #f39c12;
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
        .kpi-card.success {
            border-left-color: var(--green-color);
        }
        .kpi-card.success .value {
            color: var(--green-color);
        }

        /* --- Main Grid (Charts, Stock, Orders) --- */
        .main-grid {
            display: grid;
            /* --- MODIFICATION HERE --- */
            /* Changed from 1fr 1fr to 1fr 1.5fr to give table more space */
            grid-template-columns: 1fr 1.5fr; 
            gap: 30px;
            margin-bottom: 30px;
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

        /* --- Full Width Card --- */
        .full-width-card {
            grid-column: 1 / -1;
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
            /* Prevent text wrapping to help with layout */
            white-space: nowrap; 
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
            white-space: normal; /* Allow product details to wrap */
        }
        .styled-table .shortage {
            color: var(--red-color);
            font-weight: 700;
        }
        .styled-table .excess {
            color: var(--green-color);
            font-weight: 700;
        }
        .styled-table .recommend {
            color: var(--primary-color);
            font-weight: 700;
            background: #f4f0ff;
            border-radius: 6px;
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
            white-space: nowrap;
        }
        .btn-deliver {
            background-color: var(--green-color);
            color: white;
        }
        .btn-deliver:hover {
            background-color: #27ae60;
        }
        .btn-add {
            background-color: var(--orange-color);
            color: white;
        }
        .btn-add:hover {
            background-color: #e67e22;
        }
        
        /* --- Responsive --- */
        @media (max-width: 1200px) {
            .main-grid {
                /* Stack on smaller screens */
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
            }
            .kpi-grid {
                gap: 15px;
            }
            .main-grid {
                gap: 20px;
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
        <h1>Admin Dashboard</h1>
        <a href="index.html" class="logout-btn">Logout</a>
    </header>

    <div class="dashboard-container">
        
        <div class="kpi-grid">
            <div class="kpi-card alert">
                <h3>Pending Orders</h3>
                <div class="value" id="kpi-pending-orders">...</div>
            </div>
            <div class="kpi-card">
                <h3>Pending Revenue</h3>
                <div class="value" id="kpi-pending-revenue">...</div>
            </div>
            <div class="kpi-card alert" id="kpi-stock-alert-card">
                <h3>Stock Alerts</h3>
                <div class="value" id="kpi-stock-alerts">...</div>
            </div>
        </div>

        <div class="main-grid">
            
            <div class="card">
                <div class="card-header">
                    <h2>Stock vs. Demand</h2>
                </div>
                <div class="card-body">
                    <canvas id="stockChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Stock Management & Prediction</h2>
                </div>
                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="styled-table" id="stock_details">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Demand</th>
                                    <th>In Stock</th>
                                    <th>Shortage</th>
                                    <th>Excess</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="stock_details_body">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card full-width-card">
                <div class="card-header">
                    <h2>Pending Orders</h2>
                </div>
                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="styled-table" id="dataset">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Customer</th>
                                    <th>Account</th>
                                    <th>Amount</th>
                                    <th>Paid At</th>
                                    <th>Products</th>
                                    <th>Destination</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="dataset_body">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Global variable for the chart
        var myStockChart;
        
        // Function to format currency
        function formatCurrency(value) {
            // Using BDT, but you can change 'en-IN' and 'BDT'
            return new Intl.NumberFormat('en-IN', { 
                style: 'currency', 
                currency: 'BDT',
                minimumFractionDigits: 2
            }).format(value);
        }

        // Function to initialize the chart
        function initChart() {
            const ctx = document.getElementById('stockChart').getContext('2d');
            myStockChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [], // Will be populated by AJAX
                    datasets: [
                        {
                            label: 'In Stock',
                            data: [],
                            backgroundColor: 'rgba(52, 152, 219, 0.7)',
                            borderColor: 'rgba(52, 152, 219, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Pending Demand',
                            data: [],
                            backgroundColor: 'rgba(230, 126, 34, 0.7)',
                            borderColor: 'rgba(230, 126, 34, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Shortage',
                            data: [],
                            backgroundColor: 'rgba(231, 76, 60, 0.7)',
                            borderColor: 'rgba(231, 76, 60, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                // Ensure only whole numbers are shown on Y-axis
                                precision: 0 
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Function to fetch and update all data
        function updateDashboard() {
            $.ajax({
                url: 'admin.php?json_data=true',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    
                    // 1. Update KPI Cards
                    $('#kpi-pending-orders').text(data.kpi.pending_orders);
                    $('#kpi-pending-revenue').text(formatCurrency(data.kpi.pending_revenue));
                    $('#kpi-stock-alerts').text(data.kpi.stock_alerts);
                    
                    if(data.kpi.stock_alerts > 0) {
                        $('#kpi-stock-alert-card').addClass('alert');
                    } else {
                        $('#kpi-stock-alert-card').removeClass('alert').addClass('success');
                        $('#kpi-stock-alerts').text("All Good");
                    }

                    // 2. Update Stock Table
                    let stockHtml = '';
                    data.stock.forEach(item => {
                        stockHtml += `
                            <tr>
                                <td><strong>${item.name}</strong></td>
                                <td>${item.demand}</td>
                                <td>${item.stock}</td>
                                <td class="${item.shortage > 0 ? 'shortage' : ''}">${item.shortage}</td>
                                <td class="${item.excess > 0 ? 'excess' : ''}">${item.excess}</td>
                                <td><a href='buy.php?product_id=${item.pid}' class='btn btn-add'>ADD</a></td>
                            </tr>
                        `;
                    });
                    $('#stock_details_body').html(stockHtml || "<tr><td colspan='7'>No stock data found.</td></tr>");

                    // 3. Update Orders Table
                    let ordersHtml = '';
                    data.orders.forEach(order => {
                        ordersHtml += `
                            <tr>
                                <td>${order.pay_id}</td>
                                <td>${order.customer_name}<br><small>${order.customer_email}</small></td>
                                <td>${order.account_number}</td>
                                <td><strong>${formatCurrency(order.total_amount)}</strong></td>
                                <td>${order.payment_time}</td>
                                <td class="product-details">${order.product_details}</td>
                                <td>${order.destination}</td>
                                <td><a href='delivery.php?pay_id=${order.pay_id}' class='btn btn-deliver'>Deliver</a></td>
                            </tr>
                        `;
                    });
                    $('#dataset_body').html(ordersHtml || "<tr><td colspan='8'>No pending orders found.</td></tr>");

                    // 4. Update Chart
                    if (myStockChart) {
                        myStockChart.data.labels = data.chart_data.labels; // <-- DYNAMIC LABELS
                        myStockChart.data.datasets[0].data = data.chart_data.stock;
                        myStockChart.data.datasets[1].data = data.chart_data.demand;
                        myStockChart.data.datasets[2].data = data.chart_data.shortage;
                        myStockChart.update();
                    }

                },
                error: function(xhr, status, err) {
                    console.error("Failed to update dashboard:", err);
                    console.error("Response Text:", xhr.responseText);
                    $('#dataset_body').html("<tr><td colspan='8'>Error loading data. Check console.</td></tr>");
                    $('#stock_details_body').html("<tr><td colspan='7'>Error loading data. Check console.</td></tr>");
                }
            });
        }

        // On Document Ready
        $(document).ready(function() {
            initChart();      // Create the chart
            updateDashboard();  // Load data immediately
            
            // Refresh data every 10 seconds (2000ms)
            setInterval(updateDashboard, 2000); 
        });
    </script>
</body>
</html>
