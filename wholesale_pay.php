<?php
require "config.php";
require "local_time.php";
require "admin_details.php";

// Sanitize all POST data on arrival
$total_price = htmlspecialchars($_POST["tp"] ?? "0", ENT_QUOTES, 'UTF-8');
$lpc = htmlspecialchars($_POST["lpc"] ?? "0", ENT_QUOTES, 'UTF-8');
$mpc = htmlspecialchars($_POST["mpc"] ?? "0", ENT_QUOTES, 'UTF-8');
$cpc = htmlspecialchars($_POST["cpc"] ?? "0", ENT_QUOTES, 'UTF-8');
$addr = htmlspecialchars($_POST["addr"] ?? "", ENT_QUOTES, 'UTF-8');

// Set variables for the Admin
$email = $admin_email;
$name = $admin_name;
$account_number = $admin_account_number;
// We NO LONGER use the hardcoded $secret here for security
$ecommerce_account = $supplier_account_number; // Admin is paying the Supplier
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="js/jquery.min.js"></script>
    <title>Confirm Wholesale Payment - Admin Portal</title>
    <style>
        :root {
            --primary-color: #4a00e0;
            --secondary-color: #8e2de2;
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333;
            --text-light: #555;
            --border-color: #e9e9e9;
            --red-color: #e74c3c;
            --green-color: #2ecc71;
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

        /* --- Payment Card --- */
        .payment-container {
            width: 100%;
            max-width: 480px;
        }
        .payment-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 2.5rem;
            text-align: center;
        }
        .payment-card h1 {
            color: var(--text-color);
            font-size: 1.8rem;
            margin-top: 0;
            margin-bottom: 1rem;
        }

        /* Payment Summary */
        .payment-summary {
            background: var(--bg-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .payment-summary p {
            margin: 0;
            color: var(--text-light);
            font-size: 1rem;
            line-height: 1.5;
        }
        .payment-summary .amount {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-top: 0.5rem;
            display: block;
        }
        .payment-summary strong {
            color: var(--text-color);
        }
        
        /* Form Styling */
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .form-group input[type="password"] {
            width: 100%;
            padding: 14px;
            border: none;
            background-color: var(--bg-color);
            border-radius: 8px;
            font-size: 1.2rem;
            box-sizing: border-box;
            border: 2px solid var(--bg-color);
            transition: border-color 0.3s ease;
            text-align: center;
            letter-spacing: 2px;
        }
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        /* Button Styling */
        .button {
            display: block;
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .pay-button {
            background: var(--gradient);
            color: white;
            background-size: 200% auto;
            margin-bottom: 1rem;
        }
        .pay-button:hover {
            background-position: right center;
            box-shadow: 0 5px 15px rgba(74, 0, 224, 0.3);
            transform: translateY(-2px);
        }
        .pay-button:disabled {
            background: var(--text-light);
            cursor: not-allowed;
            opacity: 0.7;
        }
        .pay-button svg {
            vertical-align: middle;
            margin-right: 8px;
            position: relative;
            top: -1px;
        }
        
        .cancel-button {
            background: none;
            color: var(--text-light);
            border: 2px solid var(--border-color);
        }
        .cancel-button:hover {
            background: var(--bg-color);
            color: var(--red-color);
            border-color: var(--red-color);
        }
        
        /* Result Message */
        #result {
            font-size: 1rem;
            font-weight: 600;
            margin-top: 1.5rem;
            display: none; /* Hidden by default */
        }
        #result.success { color: var(--green-color); }
        #result.error { color: var(--red-color); }

        /* Spinner for loading */
        .spinner {
            display: inline-block;
            width: 1em;
            height: 1em;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
            vertical-align: middle;
            position: relative;
            top: -1px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

    </style>
        <link rel="icon" href="shop_icon.png" type="image/x-icon">

</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <h1>Confirm Supply Order</h1>

            <div class="payment-summary">
                <p>You are about to pay:</p>
                <span class="amount"><?php echo $total_price; ?> BDT</span>
                <p>From Admin Account: <strong><?php echo $account_number; ?></strong></p>
                <p>To Supplier Account: <strong><?php echo $ecommerce_account; ?></strong></p>
            </div>
            
            <form>
                <input type="text" id="sender" value="<?php echo $account_number; ?>" hidden>
                <input type="text" id="receiver" value="<?php echo $ecommerce_account; ?>" hidden>
                <input type="text" id="amount" value="<?php echo $total_price; ?>" hidden>
                <input type="text" id="time" value="<?php echo $time; ?>" hidden>
                <input type="text" id="lpc" value="<?php echo $lpc; ?>" hidden>
                <input type="text" id="mpc" value="<?php echo $mpc; ?>" hidden>
                <input type="text" id="cpc" value="<?php echo $cpc; ?>" hidden>
                <input type="text" id="addr" value="<?php echo $addr; ?>" hidden>
                
                <div class="form-group">
                    <label for="secret_pin">Admin Transaction Secret</label>
                    <input type="password" id="secret_pin" required>
                </div>
                
                <button type="button" id="pay" class="button pay-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2zM9 11.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                    </svg>
                    Pay <?php echo $total_price; ?> BDT
                </button>
            </form>
            <button id="cancel_payment" class="button cancel-button">Cancel Payment</button>
            
            <div id="result"></div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            
            const payButton = $("#pay");
            const cancelButton = $("#cancel_payment");
            const resultDiv = $("#result");
            const payButtonText = payButton.html();

            function showResult(message, type) {
                resultDiv.text(message).removeClass('success error').addClass(type).fadeIn();
            }

            function redirectToMarket(message, type) {
                // Redirects to buy.php (admin market)
                showResult(message + " You will be redirected in 5 seconds...", type);
                
                payButton.prop('disabled', true);
                cancelButton.prop('disabled', true);
                
                setTimeout(function () {
                    window.location = "buy.php"; // Redirect to admin market
                }, 5000);
            }
            
            payButton.click(function () {
                payButton.prop('disabled', true).html('<span class="spinner"></span> Processing...');
                cancelButton.prop('disabled', true);

                $.ajax({
                    method: "POST",
                    url: "transaction.php", // Uses the SAME transaction.php
                    data: {
                        sender: $("#sender").val(),
                        receiver: $("#receiver").val(),
                        amount: $("#amount").val(),
                        time: $("#time").val(),
                        secret_pin: $("#secret_pin").val(),
                        lpc: $("#lpc").val(),
                        mpc: $("#mpc").val(),
                        cpc: $("#cpc").val(),
                        addr: $("#addr").val()
                    },
                    dataType: 'json',
                    success: function (data) {
                        if (data.color === 'green') {
                            redirectToMarket(data.status, 'success');
                        } else {
                            showResult(data.status, 'error');
                            payButton.prop('disabled', false).html(payButtonText);
                            cancelButton.prop('disabled', false);
                        }
                    },
                    error: function () {
                        showResult("An unknown error occurred. Please try again.", 'error');
                        payButton.prop('disabled', false).html(payButtonText);
                        cancelButton.prop('disabled', false);
                    }
                });
            });

            cancelButton.click(function () {
                redirectToMarket("Payment Cancelled by You.", 'error');
            });
        });
    </script>
</body>
</html>
