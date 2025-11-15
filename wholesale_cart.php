<?php
require "config.php";
require "local_time.php";

// Get data from URL and sanitize it
$laptop = htmlspecialchars($_GET["laptop"] ?? "0", ENT_QUOTES, 'UTF-8');
$mobile = htmlspecialchars($_GET["mobile"] ?? "0", ENT_QUOTES, 'UTF-8');
$calculator = htmlspecialchars($_GET["calculator"] ?? "0", ENT_QUOTES, 'UTF-8');
$lp = htmlspecialchars($_GET["lp"] ?? "0", ENT_QUOTES, 'UTF-8');
$mp = htmlspecialchars($_GET["mp"] ?? "0", ENT_QUOTES, 'UTF-8');
$cp = htmlspecialchars($_GET["cp"] ?? "0", ENT_QUOTES, 'UTF-8');

$total_price = (int)$lp + (int)$mp + (int)$cp;
$is_cart_empty = ($laptop === "0" && $mobile === "0" && $calculator === "0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="js/jquery.min.js"></script>
    <title>Wholesale Cart - Admin Portal</title>
    <style>
        :root {
            --primary-color: #4a00e0;
            --secondary-color: #8e2de2;
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333;
            --text-light: #555;
            --border-color: #e9e9e9;
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
        }

        .navbar-links a {
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 600;
            border: 2px solid var(--primary-color);
            padding: 8px 18px;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .navbar-links a:hover {
            background: var(--primary-color);
            color: white;
        }
        
        /* --- Main Cart Layout --- */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
        }

        .cart-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .cart-items-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .cart-items-list {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cart-table th, .cart-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        .cart-table th {
            background-color: #fcfcfc;
            color: var(--text-light);
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        
        .item-image {
            width: 70px;
            height: 70px;
            object-fit: contain;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        
        .cart-table td.item-name {
            font-size: 1.1rem;
            font-weight: 600;
        }
        .cart-table td.item-quantity {
            font-size: 1.1rem;
        }
        .cart-table td.item-price {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .shop-more-button {
            background: var(--card-bg);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 15px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .shop-more-button:hover {
            background: var(--primary-color);
            color: var(--card-bg);
        }
        
        /* --- Order Summary --- */
        .order-summary {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 2rem;
            position: sticky;
            top: 90px;
        }
        
        .order-summary h2 {
            margin-top: 0;
            font-size: 1.5rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.75rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .form-group input[type="text"] {
            width: 100%;
            padding: 14px;
            border: none;
            background-color: var(--bg-color);
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            border: 2px solid var(--bg-color);
            transition: border-color 0.3s ease;
        }
        .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .price-breakdown {
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }
        .price-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            color: var(--text-light);
        }
        .price-line span:last-child {
            font-weight: 600;
            color: var(--text-color);
        }
        .price-total {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-color);
            border-top: 2px solid var(--border-color);
            padding-top: 1rem;
        }
        .price-total .total-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .confirm-button {
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
            background: var(--gradient);
            color: white;
            background-size: 200% auto;
        }
        .confirm-button:hover {
            background-position: right center;
            box-shadow: 0 5px 15px rgba(74, 0, 224, 0.3);
            transform: translateY(-2px);
        }
        .confirm-button svg {
            vertical-align: middle;
            margin-right: 8px;
            position: relative;
            top: -1px;
        }

        /* --- Empty Cart Styling --- */
        .empty-cart-container {
            text-align: center;
            background: var(--card-bg);
            padding: 4rem 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-top: 2rem;
        }
        .empty-cart-container h1 {
            font-size: 2rem;
            color: var(--primary-color);
        }
        .empty-cart-container p {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 2rem;
        }
        
        /* --- Responsive --- */
        @media (max-width: 992px) {
            .cart-grid {
                grid-template-columns: 1fr;
            }
            .order-summary {
                position: static;
                top: auto;
            }
        }
    </style>
        <link rel="icon" href="shop_icon.png" type="image/x-icon">

</head>
<body>

    <div class="navbar">
        <div class="navbar-container">
            <a href="admin.php" class="navbar-brand">Admin Portal</a>
            <ul class="navbar-links">
                <li><a href="admin.php">Dashboard</a></li>
            </ul>
        </div>
    </div>

    <div class="container">
        
        <?php if ($is_cart_empty): ?>
            <div class="empty-cart-container">
                <h1>Your cart is empty</h1>
                <p>Please go back to the supply market to add products.</p>
                <a href="buy.php"><button class="button confirm-button">Back to Market</button></a>
            </div>
        
        <?php else: ?>
            <h1 class="page-title">Wholesale Cart</h1>
            <div class="cart-grid">
                
                <div class="cart-items-container">
                    <div class="cart-items-list">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Pieces</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if($laptop !== "0") {
                                    echo "<tr>
                                            <td><img src='./products/laptop.png' alt='Laptop' class='item-image'></td>                 
                                            <td class='item-quantity'>$laptop</td>
                                            <td class='item-price'>$lp BDT</td>
                                          </tr>";
                                }
                                if($mobile !== "0") {
                                    echo "<tr>
                                            <td><img src='./products/mobile.png' alt='Mobile' class='item-image'></td>
                                            
                                            <td class='item-quantity'>$mobile</td>
                                            <td class='item-price'>$mp BDT</td>
                                          </tr>";
                                }
                                if($calculator !== "0") {
                                    echo "<tr>
                                            <td><img src='./products/calculator.png' alt='Calculator' class='item-image'></td>
                                           
                                            <td class='item-quantity'>$calculator</td>
                                            <td class='item-price'>$cp BDT</td>
                                          </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="buy.php" class="shop-more-button">&larr; Back to Market</a>
                </div>
                
                <div class="order-summary">
                    <form action="wholesale_pay.php" method="POST">
                        <h2>Order Summary</h2>
                        
                        <input type="text" name="tp" value="<?php echo $total_price;?>" hidden>
                        <input type="text" name="lpc" value="<?php echo $laptop;?>" hidden>
                        <input type="text" name="mpc" value="<?php echo $mobile;?>" hidden>
                        <input type="text" name="cpc" value="<?php echo $calculator;?>" hidden>
                        
                        <div class="form-group">
                            <label for="addr">Delivery Address</label>
                            <input type="text" id="addr" name="addr" placeholder="e.g., Sylhet IT Shop Warehouse" required>
                        </div>
                        
                        <div class="price-breakdown">
                            <div class="price-line">
                                <span>Subtotal</span>
                                <span><?php echo $total_price;?> BDT</span>
                            </div>
                            <div class="price-line">
                                <span>Delivery Fee</span>
                                <span>0 BDT</span>
                            </div>
                            <div class="price-total">
                                <span>Total</span>
                                <span class="total-value"><?php echo $total_price;?> BDT</span>
                            </div>
                        </div>
                        
                        <button type="submit" name="confirm" class="confirm-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                              <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2zM9 11.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            </svg>
                            Confirm & Pay
                        </button>
                    </form>
                </div>
                
            </div>
        <?php endif; ?>
        
    </div>
</body>
</html>
