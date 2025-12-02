<?php
require "config.php";
session_start(); // Start session to check login status

// Get data from cart.php (POST)
$total_price = $_POST["tp"] ?? 0;
$lpc = $_POST["lpc"] ?? 0;
$mpc = $_POST["mpc"] ?? 0;
$cpc = $_POST["cpc"] ?? 0;
$addr = $_POST["addr"] ?? '';

// If accessing directly without data, go back to shop
if ($total_price <= 0) {
    header("Location: market.php");
    exit;
}

// Store cart in session for the API to use later
$_SESSION['cart_data'] = [
    'total_price' => $total_price,
    'lpc' => $lpc, 'mpc' => $mpc, 'cpc' => $cpc,
    'addr' => $addr
];

// Check if user is logged in
$is_logged_in = isset($_SESSION['email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment - Sylhet IT Shop</title>
    
    <script src="https://js.stripe.com/v3/"></script>
    <script src="js/jquery.min.js"></script>
    <style>
        :root {
            --primary-color: #4a00e0;
            --secondary-color: #8e2de2;
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333;
            --red-color: #e74c3c;
            --gradient: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        /* Reset and Base Styles */
        * {
            box-sizing: border-box; /* Ensures padding doesn't increase width */
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--gradient);
            display: flex; 
            align-items: center; 
            justify-content: center;
            min-height: 100vh;
            margin: 0; 
            padding: 20px;
            color: var(--text-color);
        }

        .payment-card {
            width: 100%; 
            max-width: 480px;
            background: var(--card-bg); 
            border-radius: 16px;
            padding: 2.5rem; 
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        /* Mobile Optimization */
        @media (max-width: 480px) {
            body {
                padding: 10px; /* Less padding on edge of screen */
            }
            .payment-card {
                padding: 1.5rem; /* Less padding inside card */
            }
            h2 {
                font-size: 1.5rem;
            }
        }

        h2 { margin-top: 0; color: var(--primary-color); }
        p { color: #666; margin-bottom: 2rem; line-height: 1.5; }
        
        /* Button Styles */
        .button {
            display: block; 
            width: 100%; 
            padding: 15px; 
            font-size: 1.1rem;
            font-weight: 600; 
            border: none; 
            border-radius: 8px;
            cursor: pointer; 
            transition: all 0.3s ease;
            background: var(--gradient); 
            color: white;
            text-decoration: none;
            margin-top: 1rem;
        }
        .button:hover {
            box-shadow: 0 5px 15px rgba(74, 0, 224, 0.3);
            transform: translateY(-2px);
        }
        .button:disabled { 
            background: #999; 
            cursor: not-allowed; 
            transform: none; 
            box-shadow: none; 
        }
        
        /* Error Message */
        #error-message { color: var(--red-color); font-weight: 600; margin-top: 1rem; }

        /* Login Prompt Specifics */
        .login-icon {
            font-size: 3rem; margin-bottom: 1rem; display: block;
        }
    </style>
    <link rel="icon" href="shop_icon.png" type="image/x-icon">
</head>
<body>

    <?php if (!$is_logged_in): ?>
        <div class="payment-card">
            <span class="login-icon">🔒</span>
            <h2>Login Required</h2>
            <p>You must be logged in to complete your order securely.</p>
            
            <a href="customer_login.php" class="button">Login to Continue</a>
            <br>
            <a href="market.php" style="color: #666; text-decoration: none; font-size: 0.9rem;">Cancel and return to shop</a>
        </div>

    <?php else: ?>
        <div class="payment-card">
            <h2>Pay with Card</h2>
            <p>Securely enter your card details. We do not store your card info.</p>
            
            <form id="payment-form">
                <div id="payment-element">
                    </div>
                <button id="submit" class="button">
                    <span id="button-text">Pay <?php echo $total_price; ?> BDT</span>
                </button>
                <div id="error-message"></div>
            </form>
        </div>

        <script>
            $(document).ready(function() {
                // Your Stripe Publishable Key
                const stripe = Stripe('pk_test_51SXbxRRmuGPEAJFEXVeSkgBVc0OCjGxe6SPlfghI4siwFDaGVwYMx3XjDz8C82gEdCybM01cTn3MSdj2dUbUJWfe00KBldVLgT'); // PUT YOUR KEY HERE
                
                let elements;
                initialize();

                $('#payment-form').on('submit', handleSubmit);

                async function initialize() {
                    // Fetch the client_secret from your server
                    const response = await fetch('create_payment_intent.php', {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                    });
                    
                    const { clientSecret, error } = await response.json();

                    if (error) {
                        $('#error-message').text("Error starting payment: " + error);
                        $('#submit').prop('disabled', true);
                        return;
                    }

                    // Customize Stripe Elements appearance
                    const appearance = {
                        theme: 'stripe',
                        variables: {
                            colorPrimary: '#4a00e0',
                        },
                    };

                    elements = stripe.elements({ clientSecret, appearance });
                    const paymentElement = elements.create("payment");
                    paymentElement.mount("#payment-element");
                }

                async function handleSubmit(e) {
                    e.preventDefault();
                    setLoading(true);

                    const { error } = await stripe.confirmPayment({
                        elements,
                        confirmParams: {
                            // Return URL for localhost XAMPP
                            return_url: "http://localhost/Sylhet_IT_Shop/stripe_success.php",
                        },
                    });

                    if (error) {
                        $('#error-message').text(error.message);
                        setLoading(false);
                    }
                }

                function setLoading(isLoading) {
                    if (isLoading) {
                        $('#submit').prop('disabled', true).text("Processing...");
                    } else {
                        $('#submit').prop('disabled', false).text("Pay <?php echo $total_price; ?> BDT");
                    }
                }
            });
        </script>
    <?php endif; ?>

</body>
</html>