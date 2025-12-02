<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- CRITICAL: Makes page responsive on mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Sylhet IT Shop</title>
    <link rel="icon" href="shop_icon.png" type="image/x-icon">
    <!-- Confetti Library for celebration animation -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        :root {
            --primary-color: #4a00e0;
            --secondary-color: #8e2de2;
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333;
            --text-light: #555;
            --green-color: #2ecc71;
            --green-bg: #eafaf1;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --gradient: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 20px;
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 3rem 2rem;
            text-align: center;
            max-width: 480px;
            width: 100%;
            /* Simple entrance animation */
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .icon-container {
            width: 80px;
            height: 80px;
            background-color: var(--green-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto;
        }

        .success-icon {
            color: var(--green-color);
            width: 40px;
            height: 40px;
        }

        h1 {
            color: var(--text-color);
            font-size: 2rem;
            margin: 0 0 1rem 0;
            font-weight: 700;
        }

        p {
            color: var(--text-light);
            font-size: 1.1rem;
            line-height: 1.6;
            margin: 0 0 1.5rem 0;
        }

        .button {
            display: inline-block;
            background: var(--gradient);
            color: white;
            padding: 15px 35px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(74, 0, 224, 0.3);
        }

        .button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(74, 0, 224, 0.4);
        }

        /* Mobile Optimization */
        @media (max-width: 480px) {
            .success-card {
                padding: 2rem 1.5rem;
            }
            h1 {
                font-size: 1.75rem;
            }
            p {
                font-size: 1rem;
            }
            .button {
                width: 100%; /* Full width button on phone */
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="icon-container">
            <!-- Checkmark Icon -->
            <svg class="success-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        
        <h1>Payment Successful!</h1>
        
        <p>
            Thank you for your order. Your payment has been processed and your items are confirmed.
        </p>
        <p style="font-size: 0.95rem; margin-top: -1rem; color: #888;">
            You can track your order status in your profile.
        </p>
        
        <a href="customer_profile.php" class="button">Go to My Profile</a>
    </div>

    <script>
        // Run confetti animation on load
        window.onload = function() {
            var count = 200;
            var defaults = {
                origin: { y: 0.7 }
            };

            function fire(particleRatio, opts) {
                confetti(Object.assign({}, defaults, opts, {
                    particleCount: Math.floor(count * particleRatio)
                }));
            }

            fire(0.25, { spread: 26, startVelocity: 55, });
            fire(0.2, { spread: 60, });
            fire(0.35, { spread: 100, decay: 0.91, scalar: 0.8 });
            fire(0.1, { spread: 120, startVelocity: 25, decay: 0.92, scalar: 1.2 });
            fire(0.1, { spread: 120, startVelocity: 45, });
        };
    </script>
</body>
</html>