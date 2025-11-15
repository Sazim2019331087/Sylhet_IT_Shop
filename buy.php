<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="js/jquery.min.js"></script>
    <title>Supply Market - Admin Portal</title>
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

        /* --- Navbar --- */
        .navbar {
            background: var(--card-bg);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            height: 70px;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .navbar-links {
            display: flex;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 1.5rem;
        }

        .navbar-links a {
            text-decoration: none;
            color: var(--text-light);
            font-weight: 500;
            transition: color 0.3s ease;
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 8px 18px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .navbar-links a:hover {
            background: var(--primary-color);
            color: white;
        }

        .cart-button {
            background: var(--gradient);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .cart-button:hover {
            box-shadow: 0 5px 15px rgba(74, 0, 224, 0.3);
            transform: translateY(-2px);
        }
        
        #cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--red-color);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            display: none; /* Hidden by default */
        }

        /* --- Page Header --- */
        .container {
            max-width: 1200px;
            margin: 0 auto 3rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 2rem 0;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }
        
        #reset_all {
            background-color: var(--red-light-bg);
            border: 1px solid var(--red-light-border);
            color: var(--red-color);
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 10px 15px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        #reset_all:hover {
            background-color: var(--red-color);
            color: white;
            border-color: var(--red-color);
        }

        /* --- Product Grid --- */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            text-align: center;
            padding: 1.5rem;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #e8ecf3 100%);
        }

        .product-card img {
            width: 100%;
            max-width: 250px;
            height: 200px;
            object-fit: contain;
        }
        
        .product-info {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .product-card h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            color: var(--text-color);
        }
        
        .product-price {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0.5rem 0 1rem 0;
        }

        .product-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto; /* Pushes controls to the bottom */
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-btn {
            background-color: var(--bg-color);
            border: 1px solid #ddd;
            color: var(--text-color);
            width: 35px;
            height: 35px;
            border-radius: 50%;
            font-size: 1.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .quantity-btn:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .quantity-display {
            font-size: 1.2rem;
            font-weight: 600;
            min-width: 25px;
            text-align: center;
        }

        .reset-btn {
            background-color: var(--red-light-bg);
            border: 1px solid var(--red-light-border);
            color: var(--red-color);
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .reset-btn:hover {
            background-color: var(--red-color);
            color: white;
            border-color: var(--red-color);
        }
        
        /* Renaming original buttons to icons */
        .inc-btn {
             /* Original #incXXX */
        }
        .dec-btn {
             /* Original #decXXX */
        }
        
    </style>
        <link rel="icon" href="shop_icon.png" type="image/x-icon">

</head>

<body>
    <div class="navbar">
        <div class="navbar-container">
            <a href="admin.php" class="navbar-brand">Admin Portal</a>
            <ul class="navbar-links">
                <li><a href="admin.php" class="nav-button-secondary">Dashboard</a></li>
                <li>
                    <button id="open_cart" class="cart-button">
                        Cart
                        <span id="cart-badge">0</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <div class="container">
        
        <div class="page-header">
            <h1>Supply Market</h1>
            <button id="reset_all">Reset All Selections</button>
        </div>
        
        <div class="product-grid">
            
            <div class="product-card">
                <div class="product-image">
                    <img src="./products/laptop.png" alt="Laptop">
                </div>
                <div class="product-info">
                    <h2>Laptop</h2>
                    <p class="product-price">Total Price: <span id="price111">0</span> Tk</p>
                    <div class="product-controls">
                        <div class="quantity-controls">
                            <button id="dec111" class="quantity-btn dec-btn">-</button>
                            <span id="amount111" class="quantity-display">0</span>
                            <button id="inc111" class="quantity-btn inc-btn">+</button>
                        </div>
                        <button id="reset111" class="reset-btn">Clear</button>
                    </div>
                </div>
            </div>

            <div class="product-card">
                <div class="product-image">
                    <img src="./products/mobile.png" alt="Mobile">
                </div>
                <div class="product-info">
                    <h2>Mobile</h2>
                    <p class="product-price">Total Price: <span id="price222">0</span> Tk</p>
                    <div class="product-controls">
                        <div class="quantity-controls">
                            <button id="dec222" class="quantity-btn dec-btn">-</button>
                            <span id="amount222" class="quantity-display">0</span>
                            <button id="inc222" class="quantity-btn inc-btn">+</button>
                        </div>
                        <button id="reset222" class="reset-btn">Clear</button>
                    </div>
                </div>
            </div>

            <div class="product-card">
                <div class="product-image">
                    <img src="./products/calculator.png" alt="Calculator">
                </div>
                <div class="product-info">
                    <h2>Calculator</h2>
                    <p class="product-price">Total Price: <span id="price333">0</span> Tk</p>
                    <div class="product-controls">
                        <div class="quantity-controls">
                            <button id="dec333" class="quantity-btn dec-btn">-</button>
                            <span id="amount333" class="quantity-display">0</span>
                            <button id="inc333" class="quantity-btn inc-btn">+</button>
                        </div>
                        <button id="reset333" class="reset-btn">Clear</button>
                    </div>
                </div>
            </div>

        </div> </div> <script>
        $(document).ready(function () {
            
            // --- Cart Badge Function ---
            function updateCartBadge() {
                let total = 0;
                total += parseInt($("#amount111").text()) || 0;
                total += parseInt($("#amount222").text()) || 0;
                total += parseInt($("#amount333").text()) || 0;
                
                let badge = $("#cart-badge");
                if (total > 0) {
                    badge.text(total).fadeIn();
                } else {
                    badge.fadeOut();
                }
            }

            // --- Original AJAX Function (Modified for efficiency) ---
            function updateAmountAndPrice(productId, amountId, priceId, url) {
                $.ajax({
                    method: "POST",
                    url: url,
                    data: { amount: $(amountId).text() },
                    success: function (response) {
                        let data = JSON.parse(response);
                        $(amountId).text(data.amount);
                        $(priceId).text(data.price);
                        updateCartBadge(); // Update badge on success
                    }
                });
            }

            // --- Click Handlers ---
            $("#dec111").click(function () {
                updateAmountAndPrice('111', '#amount111', '#price111', 'decrement111.php');
            });
            $("#dec222").click(function () {
                updateAmountAndPrice('222', '#amount222', '#price222', 'decrement222.php');
            });
            $("#dec333").click(function () {
                updateAmountAndPrice('333', '#amount333', '#price333', 'decrement333.php');
            });

            $("#inc111").click(function () {
                updateAmountAndPrice('111', '#amount111', '#price111', 'increment111.php');
            });
            $("#inc222").click(function () {
                updateAmountAndPrice('222', '#amount222', '#price222', 'increment222.php');
            });
            $("#inc333").click(function () {
                updateAmountAndPrice('333', '#amount333', '#price333', 'increment333.php');
            });

            // --- Reset Handlers ---
            $("#reset111").click(function () {
                $("#price111").text(0);
                $("#amount111").text(0);
                updateCartBadge();
            });
            $("#reset222").click(function () {
                $("#price222").text(0);
                $("#amount222").text(0);
                updateCartBadge();
            });
            $("#reset333").click(function () {
                $("#price333").text(0);
                $("#amount333").text(0);
                updateCartBadge();
            });
            
            // --- New "Reset All" Button ---
            $("#reset_all").click(function () {
                $("#reset111, #reset222, #reset333").click(); // Triggers all individual resets
            });

            // --- Go to Cart Handler (Corrected URL) ---
            $("#open_cart").click(function () {
                var url = "wholesale_cart.php?laptop=" + $("#amount111").text() + 
                          "&mobile=" + $("#amount222").text() + 
                          "&calculator=" + $("#amount333").text() + 
                          "&lp=" + $("#price111").text() + 
                          "&mp=" + $("#price222").text() + 
                          "&cp=" + $("#price333").text();
                window.location.href = url;
            });
            
            // --- Run on page load ---
            updateCartBadge();
        });
    </script>
</body>

</html>
