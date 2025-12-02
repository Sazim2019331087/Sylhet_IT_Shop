<?php
require "config.php";
require "local_time.php";

// --- Helper Functions ---

/**
 * Masks an account number, showing only the last 4 digits.
 * @param string $number The account number.
 * @return string The masked account number.
 */
function mask_account($number) {
    return "**** **** **** " . substr($number, -4);
}

// --- Data Fetching ---

$account_number = $_GET["account_number"] ?? ''; // Use null coalescing for safety

if (empty($account_number)) {
    die("No account number provided.");
}

// 1. Get User Details (Using Prepared Statement)
$name = "";
$email = "";
$balance = 0;

$stmt = $con->prepare("SELECT name, email, current_balance FROM bank_details WHERE account_number = ?");
$stmt->bind_param("s", $account_number);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $r = $result->fetch_assoc();
    $name = $r["name"];
    $email = $r["email"];
    $balance = $r["current_balance"];
}
$stmt->close();

// 2. Get Total Debit (Sent)
$stmt_debit = $con->prepare("SELECT SUM(amount) as total_debit FROM bank_payment_details WHERE sender_account = ?");
$stmt_debit->bind_param("s", $account_number);
$stmt_debit->execute();
$r_debit = $stmt_debit->get_result()->fetch_assoc();
$total_debit = $r_debit['total_debit'] ?? 0;
$stmt_debit->close();

// 3. Get Total Credit (Received)
$stmt_credit = $con->prepare("SELECT SUM(amount) as total_credit FROM bank_payment_details WHERE receiver_account = ?");
$stmt_credit->bind_param("s", $account_number);
$stmt_credit->execute();
$r_credit = $stmt_credit->get_result()->fetch_assoc();
$total_credit = $r_credit['total_credit'] ?? 0;
$stmt_credit->close();

// 4. Get Transaction Data (Using Prepared Statement)
// Note: The STR_TO_DATE logic is preserved from your original code.
// Storing dates in a proper DATETIME/TIMESTAMP column is highly recommended.
$sql_all_transactions = "
    SELECT 
        payment_id, sender_account, receiver_account, amount, payment_time,
        CASE
            WHEN sender_account = ? THEN 'SENT'
            WHEN receiver_account = ? THEN 'RECEIVED'
        END AS transaction_type
    FROM 
        bank_payment_details
    WHERE 
        sender_account = ? OR receiver_account = ?
    ORDER BY
        STR_TO_DATE(
            REPLACE(REPLACE(REPLACE(REPLACE(payment_time, 'st', ''), 'nd', ''), 'rd', ''), 'th', ''),
            '%h:%i:%s %p %d %M , %Y %W'
        ) DESC";

$stmt_trans = $con->prepare($sql_all_transactions);
// Bind the same $account_number to all four placeholders
$stmt_trans->bind_param("ssss", $account_number, $account_number, $account_number, $account_number);
$stmt_trans->execute();
$query_all_transactions = $stmt_trans->get_result();
$total_row = $query_all_transactions->num_rows;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="js/jquery.min.js"></script>
    <title>Account Dashboard - SUSTainable Bank</title>
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --green-color: #2ecc71;
            --red-color: #e74c3c;
            --light-grey: #f4f4f9;
            --dark-text: #333;
            --light-text: #fdfdfd;
            --border-color: #ddd;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--light-grey);
            margin: 0;
            padding: 20px;
        }

        .dashboard-container {
            width: 95%;
            max-width: 1200px;
            margin: 20px auto;
        }

        /* --- Header --- */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            /* --- GRADIENT ADDED --- */
            background: linear-gradient(135deg, #ffffff 0%, #f7f9fb 100%);
        }

        .dashboard-header h1 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.8rem;
        }
        
        .welcome-message {
            font-size: 1.1rem;
            color: #555;
        }

        .logout-btn {
            padding: 10px 20px;
            background-color: var(--red-color);
            color: white;
            font-size: 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }

        /* --- Card & Summary Grid --- */
        .card-summary-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        /* --- Debit Card --- */
        .card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--light-text);
            padding: 25px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 220px;
        }
        .card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        .card__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card__logo {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .card__chip {
            width: 50px;
            height: 40px;
            background-color: #f1c40f;
            border-radius: 6px;
        }
        .card__body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 1.5rem;
            letter-spacing: 2px;
            margin-top: 20px;
        }
        .card__footer {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        .card__holder-name {
            font-weight: bold;
            font-size: 1.1rem;
        }

        /* --- Summary Boxes --- */
        .summary-boxes {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .summary-box {
            /* background-color: white; <-- Removed */
            padding: 25px;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        
        .summary-box h3 {
            margin: 0 0 10px 0;
            color: #777;
            font-size: 1rem;
            text-transform: uppercase;
        }

        .summary-box p {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }

        .summary-box.balance {
            /* --- GRADIENT ADDED --- */
            background: linear-gradient(135deg, #eef7ff 0%, #ffffff 100%);
        }
        .summary-box.balance p {
            color: var(--primary-color);
        }

        .summary-box.credit {
            /* --- GRADIENT ADDED --- */
            background: linear-gradient(135deg, #eafaf1 0%, #ffffff 100%);
        }
        .summary-box.credit p {
            color: var(--green-color);
        }

        .summary-box.debit {
            /* --- GRADIENT ADDED --- */
            background: linear-gradient(135deg, #fdeded 0%, #ffffff 100%);
        }
        .summary-box.debit p {
            color: var(--red-color);
        }

        /* --- Transactions --- */
        .transactions-container {
            /* background-color: white; <-- Removed */
            padding: 25px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            /* --- GRADIENT ADDED --- */
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
        }
        .transactions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .transactions-header h2 {
            margin: 0;
            color: var(--dark-text);
        }
        #transactionSearch {
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
            width: 300px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        #all_transactions {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 1rem;
        }
        #all_transactions th,
        #all_transactions td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        #all_transactions th {
            background-color: var(--light-grey);
            color: #555;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        #all_transactions tbody tr:last-child td {
            border-bottom: none;
        }
        #all_transactions tbody tr:hover {
            background-color: #f0f0f0; /* Slightly darker hover */
        }

        .transaction-type-icon {
            display: inline-block;
            width: 28px;
            height: 28px;
            line-height: 28px;
            text-align: center;
            border-radius: 50%;
            margin-right: 10px;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .transaction-received .transaction-type-icon {
            background-color: #eafaf1;
            color: var(--green-color);
        }
        .transaction-sent .transaction-type-icon {
            background-color: #fdeded;
            color: var(--red-color);
        }
        .transaction-amount {
            font-weight: bold;
        }
        .transaction-received .transaction-amount {
            color: var(--green-color);
        }
        .transaction-sent .transaction-amount {
            color: var(--red-color);
        }
        
        .transaction-account {
            font-weight: bold;
            color: var(--dark-text);
        }

        .transaction-time {
            font-size: 0.9rem;
            color: #777;
        }

        .balance-updating {
            display: none;
            color: var(--primary-color);
            font-weight: bold;
            text-align: center;
            padding: 10px;
            background-color: #eaf5fb;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        /* --- Account Details (Original) --- */
        .account-details {
             /* background-color: white; <-- Removed */
             padding: 20px;
             border-radius: 8px;
             box-shadow: var(--shadow);
             margin-bottom: 25px;
             /* --- GRADIENT ADDED --- */
             background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
        }
        .account-details h2 {
            margin-top: 0;
            color: var(--primary-dark);
        }
        .account-details p {
             margin: 8px 0;
             font-size: 1rem;
             color: #555;
        }
        .account-details b {
            color: var(--dark-text);
            font-weight: 600;
        }

        /* --- Responsive Design --- */
        @media (max-width: 992px) {
            .card-summary-grid {
                grid-template-columns: 1fr;
            }
            .summary-boxes {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .transactions-header {
                flex-direction: column;
                align-items: stretch;
            }
            #transactionSearch {
                width: auto;
            }
            .summary-boxes {
                grid-template-columns: 1fr; /* Stack summary boxes on mobile */
            }
        }

    </style>
        <link rel="icon" href="bank_icon.png" type="image/x-icon">

</head>

<body>

    <div class="dashboard-container">
    
        <input type="text" id="user_account_number" value="<?php echo htmlspecialchars($account_number, ENT_QUOTES, 'UTF-8'); ?>" hidden>

        <div class="dashboard-header">
            <div>
                <h1>SUSTAINABLE BANK LTD</h1>
                <p class="welcome-message">
                    Welcome back, <b><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>!</b>
                </p>
            </div>
            <button class="logout-btn" onclick="window.location.href='bank_login.php'">Logout</button>
        </div>
        
        <div class="balance-updating" id="balance-updating-message"></div>

        <div class="card-summary-grid">
            
            <div class="card">
                <div>
                    <div class="card__header">
                        <div class="card__logo">SUSTAINABLE BANK</div>
                        <div class="card__chip"></div>
                    </div>
                    <div class="card__body">
                        <?php echo htmlspecialchars(mask_account($account_number), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
                <div class="card__footer">
                    <div>
                        <span style="display: block; font-size: 0.8rem;">Account Holder</span>
                        <span class="card__holder-name"><?php echo htmlspecialchars(strtoupper($name), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 0.8rem;">Expires</span>
                        <b>12/28</b>
                    </div>
                </div>
            </div>

            <div class="summary-boxes">
                <div class="summary-box balance">
                    <h3>Current Balance</h3>
                    <p>
                        <span id="user_current_balance"><?php echo number_format($balance, 2); ?></span> BDT
                    </p>
                </div>
                <div class="summary-box credit">
                    <h3>Total Credit</h3>
                    <p>+<?php echo number_format($total_credit, 2); ?> BDT</p>
                </div>
                <div class="summary-box debit">
                    <h3>Total Debit</h3>
                    <p>-<?php echo number_format($total_debit, 2); ?> BDT</p>
                </div>
            </div>
        </div>
        
        <div class="account-details">
             <h2>Account Information</h2>
             <p>Account Holder Name: <b><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></b></p>
             <p>Account Holder Email: <b><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></b></p>
             <p>Account Number: <b><?php echo htmlspecialchars($account_number, ENT_QUOTES, 'UTF-8'); ?></b></p>
        </div>


        <div class="transactions-container">
            <div class="transactions-header">
                <h2>All Transactions</h2>
                <input type="text" id="transactionSearch" placeholder="Search by ID, account, amount...">
            </div>
            
            <div class="table-wrapper" id="all_transactions_wrapper">
                <table id="all_transactions">
                    <thead>
                        <tr>
                            <th colspan="2">Details</th>
                            <th>Amount</th>
                            <th>Time</th>
                            <th>Payment ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($total_row > 0) {
                            // Reset pointer just in case, though it should be at the start
                            $query_all_transactions->data_seek(0); 
                            
                            while($row = $query_all_transactions->fetch_assoc())
                            {
                                $payment_id = htmlspecialchars($row["payment_id"], ENT_QUOTES, 'UTF-8');
                                $sender = htmlspecialchars($row["sender_account"], ENT_QUOTES, 'UTF-8');
                                $receiver = htmlspecialchars($row["receiver_account"], ENT_QUOTES, 'UTF-8');
                                $payment_time = htmlspecialchars($row["payment_time"], ENT_QUOTES, 'UTF-8');
                                $payment_type = $row["transaction_type"];
                                $payment_amount = $row["amount"];
                                
                                $acc = "";
                                $payment_symbol = "";
                                $row_class = "";
                                $icon = "";
                                $acc_label = "";
                                
                                if($payment_type === "SENT")
                                {
                                    $acc = $receiver;
                                    $payment_symbol = "- " . number_format($payment_amount, 2);
                                    $row_class = "transaction-sent";
                                    $icon = "&#8593;"; // Up arrow
                                    $acc_label = "To:";
                                }
                                else if($payment_type === "RECEIVED")
                                {
                                    $acc = $sender;
                                    $payment_symbol = "+ " . number_format($payment_amount, 2);
                                    $row_class = "transaction-received";
                                    $icon = "&#8595;"; // Down arrow
                                    $acc_label = "From:";
                                }
                                
                                echo "
                                <tr class='$row_class'>
                                    <td style='width: 40px;'>
                                        <span class='transaction-type-icon'>$icon</span>
                                    </td>
                                    <td>
                                        <div class='transaction-account'>$acc_label $acc</div>
                                    </td>
                                    <td>
                                        <span class='transaction-amount'>$payment_symbol BDT</span>
                                    </td>
                                    <td>
                                        <span class='transaction-time'>$payment_time</span>
                                    </td>
                                    <td>$payment_id</td>
                                </tr>
                                ";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align: center; padding: 20px;'>No transactions found.</td></tr>";
                        }
                        $stmt_trans->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        $(document).ready(function () {
            // Store the balance loaded with the page
            var lastKnownBalance = parseFloat('<?php echo $balance; ?>');

            // --- Live Transaction Search ---
            $("#transactionSearch").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#all_transactions tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
            
            // --- Auto-refresh Transaction Table ---
            setInterval(function () {
                // Reload the content *inside* the wrapper
                $("#all_transactions_wrapper").load(window.location.href + " #all_transactions");
            }, 5000); // Refresh every 5 seconds (more reasonable than 1s)

            // --- Auto-refresh Balance ---
            setInterval(function () {
                $.ajax({
                    method: "POST",
                    url: "balance_check.php",
                    data: {
                        account_number: $("#user_account_number").val(),
                    },
                    success: function (data) {
                        var newBalance = parseFloat(data);
                        // Check if the balance has *actually* changed
                        if (newBalance.toFixed(2) !== lastKnownBalance.toFixed(2)) {
                            
                            // Update both balance displays
                            $("#user_current_balance").text(newBalance.toFixed(2));
                            
                            // Show "Balance Updated" message
                            // .stop(true, true) clears any previous animations
                            $("#balance-updating-message")
                                .text("Balance Updated...")
                                .stop(true, true)
                                .fadeIn()
                                .fadeOut(3000);
                            
                            // Update the last known balance
                            lastKnownBalance = newBalance;
                        }
                    },
                    error: function() {
                        console.error("Failed to check balance.");
                    }
                });
            }, 2000); // Check every 2 seconds
        });
    </script>
</body>

</html>
