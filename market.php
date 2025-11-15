<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="js/jquery.min.js"></script>
    <title>Sylhet IT Shop - Market</title>
    <style>
        :root {
            --primary-color: #4a00e0;
            --secondary-color: #8e2de2;
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333;
	    --text-light: #555;
 	    --footer-bg: #2b2b2b;
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
            font-size: 1rem;
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
        }

        .navbar-links a:hover {
            color: var(--primary-color);
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

        /* --- Hero Section --- */
        .hero {
            background: var(--gradient);
            color: white;
            text-align: center;
            padding: 4rem 2rem;
            margin-bottom: 3rem;
        }
        .hero h1 {
            margin: 0;
            font-size: 2.8rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* --- Main Product Grid --- */
        .container {
            max-width: 1200px;
            margin: 0 auto 3rem auto;
            padding: 0 2rem;
        }
        
        .product-grid-header {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            border-bottom: 3px solid var(--primary-color);
            display: inline-block;
            margin-bottom: 2rem;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        /* --- Redesigned Product Card --- */
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
            /* --- GRADIENT ADDED --- */
	    background-image: linear-gradient(135deg, #f5f7fa 0%, #e8ecf3 100%);
	    cursor: pointer;
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
        
        /* --- NEW ARTISTIC PRICE STYLING --- */
        .product-unit-price {
            margin-bottom: 0.5rem; /* Space before the "Total Price" */
            display: flex;
            align-items: baseline; /* Aligns the TK text nicely */
            gap: 0.5rem;
        }

        .current-price {
            font-size: 1.5rem; /* Make it prominent */
            font-weight: 700; /* Bold */
            color: var(--primary-color);
        }

        .original-price {
            font-size: 1rem; /* Smaller */
            color: var(--text-light);
            text-decoration: line-through;
            opacity: 0.8;
        }
        /* --- END NEW STYLING --- */
        
        .product-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-light); /* De-emphasized color */
            margin: 0 0 1rem 0; /* Adjusted margin */
        }
        .product-price span {
            color: var(--text-color);
            font-weight: 700;
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
            padding: 8px 12px; /* Made it more button-like */
            border-radius: 6px; /* Rounded corners */
            transition: all 0.2s ease;
        }
        
        .reset-btn:hover {
            background-color: var(--red-color);
            color: white;
            border-color: var(--red-color);
        }
        
	.product-btn { } 
        /* --- NEW: Modal Styles --- */
        .modal-overlay {
            display: none; /* Hidden by default */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            box-sizing: border-box;
        }
        .modal-content {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 800px;
            max-height: 85vh; /* Makes it scrollable */
            overflow-y: auto;
            position: relative;
            z-index: 1001;
            /* Animation */
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.3s ease;
        }
        .modal-overlay.active .modal-content {
            transform: scale(1);
            opacity: 1;
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 2.5rem;
            color: #aaa;
            background: none;
            border: none;
            cursor: pointer;
            line-height: 1;
            padding: 0;
        }
        .modal-close:hover {
            color: var(--text-color);
        }
        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
        }
        .modal-header h2 {
            margin: 0;
            font-size: 1.8rem;
            color: var(--primary-color);
        }
        .modal-body {
            padding: 2rem;
        }
        .modal-body-layout {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        .modal-image-container img {
            width: 100%;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .modal-specs-container h3 {
            font-size: 1.3rem;
            margin-top: 0;
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            display: inline-block;
        }
        .spec-table {
            width: 100%;
            border-collapse: collapse;
        }
        .spec-table td {
            padding: 10px 5px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 1rem;
        }
        .spec-table td:first-child {
            font-weight: 600;
            color: var(--text-light);
            width: 120px;
        }

        @media (max-width: 768px) {
            .modal-body-layout {
                grid-template-columns: 1fr; /* Stack image and specs on mobile */
            }
            .modal-content {
                padding: 0;
            }
            .modal-header {
                padding: 1.5rem;
            }
            .modal-body {
                padding: 1.5rem;
            }
        }
        /* --- End of Modal Styles --- */


        /* --- Footer --- */
        footer {
            background: var(--footer-bg);
            color: #aaa;
            padding: 3rem 2rem 2rem 2rem;
        }
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }

        footer h4 {
            font-size: 1.1rem;
            color: white;
            margin-bottom: 1rem;
        }
        footer p, footer a {
            color: #aaa;
            text-decoration: none;
            line-height: 1.6;
        }
        footer ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        footer li {
            margin-bottom: 0.75rem;
        }
        footer a:hover {
            color: white;
            text-decoration: underline;
        }

        .footer-bottom {
            text-align: center;
            border-top: 1px solid #444;
            padding-top: 2rem;
            margin-top: 2rem;
            font-size: 0.9rem;
        }

    </style>
        <link rel="icon" href="shop_icon.png" type="image/x-icon">

</head>

<body>
    <div class="navbar">
        <div class="navbar-container">
            <span class="navbar-brand">Sylhet IT Shop</span>
            <ul class="navbar-links">
                <li><a href="customer_profile.php"><button class="cart-button">Profile</button></a></li>
                <li>
                    <button id="open_cart" class="cart-button">
                        Cart
                        <span id="cart-badge">0</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
    
    <div class="hero">
        <h1>Welcome to Sylhet IT Shop</h1>
        <p>Your one-stop shop for the latest tech and gadgets.</p>
    </div>

    <div class="container">
        <h2 class="product-grid-header">Explore Our Products</h2>
        
        <div class="product-grid">
            
            <div class="product-card">
                <div class="product-image" data-product="laptop">
                    <img src="./products/laptop.png" alt="Laptop">
                </div>
                <div class="product-info">
                    <h2>Laptop</h2>
                    <div class="product-unit-price">
                        <span class="current-price">TK 15000</span>
                        <span class="original-price">TK 25000</span>
                    </div>
                    <p class="product-price">Total Price: <span id="price111">0</span> Tk</p>
                    <div class="product-controls">
                        <div class="quantity-controls">
                            <button id="dec111" class="quantity-btn product-btn">-</button>
                            <span id="amount111" class="quantity-display">0</span>
                            <button id="inc111" class="quantity-btn product-btn">+</button>
                        </div>
                        <button id="reset111" class="reset-btn">Clear</button>
                    </div>
                </div>
            </div>

            <div class="product-card">
                <div class="product-image" data-product="mobile">
                    <img src="./products/mobile.png" alt="Mobile">
                </div>
                <div class="product-info">
                    <h2>Mobile</h2>
                    <div class="product-unit-price">
                        <span class="current-price">TK 10000</span>
                        <span class="original-price">TK 20000</span>
                    </div>
                    <p class="product-price">Total Price: <span id="price222">0</span> Tk</p>
                    <div class="product-controls">
                        <div class="quantity-controls">
                            <button id="dec222" class="quantity-btn product-btn">-</button>
                            <span id="amount222" class="quantity-display">0</span>
                            <button id="inc222" class="quantity-btn product-btn">+</button>
                        </div>
                        <button id="reset222" class="reset-btn">Clear</button>
                    </div>
                </div>
            </div>

            <div class="product-card">
                <div class="product-image" data-product="calculator">
                    <img src="./products/calculator.png" alt="Calculator">
                </div>
                <div class="product-info">
                    <h2>Calculator</h2>
                    <div class="product-unit-price">
                        <span class="current-price">TK 1000</span>
                        <span class="original-price">TK 2500</span>
                    </div>
                    <p class="product-price">Total Price: <span id="price333">0</span> Tk</p>
                    <div class="product-controls">
                        <div class="quantity-controls">
                            <button id="dec333" class="quantity-btn product-btn">-</button>
                            <span id="amount333" class="quantity-display">0</span>
                            <button id="inc333" class="quantity-btn product-btn">+</button>
                        </div>
                        <button id="reset333" class="reset-btn">Clear</button>
                    </div>
                </div>
            </div>

	</div> </div>


    <div class="modal-overlay" id="productModal" style="display: none;">
        <div class="modal-content">
            <button class="modal-close" id="modalCloseButton">&times;</button>
            <div class="modal-header">
                <h2 id="modalProductName">Product Name</h2>
            </div>
            <div class="modal-body">
                <div class="modal-body-layout">
                    <div class="modal-image-container">
                        <img src="" alt="Product" id="modalProductImage" style="width: 100%;">
                    </div>
                    <div class="modal-specs-container">
                        <h3>Specifications</h3>
                        <table class="spec-table" id="modalProductSpecs">
                            </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<footer>
        <div class="footer-container">
            <div>
                <h4>Sylhet IT Shop</h4>
                <p>Your one-stop shop for top tech products!</p>
            </div>
            <div>
                <h4>Contact</h4>
                <ul>
                    <li>Sylhet Sadar, Sylhet - 3100</li>
                    <li>Phone: +880-1931-317099</li>
                    <li>Email: sazim87@student.sust.edu</li>
                </ul>
            </div>
            <div>
                <h4>For Customers</h4>
                <ul>
                    <li><a href="customer_login.php">Customer Login</a></li>
                    <li><a href="customer_sign_up.php">Create Account</a></li>
                </ul>
            </div>
            <div>
                <h4>For Staff</h4>
                <ul>
                    <li><a href="operational_login.php">Admin / Supplier Login</a></li>
                    <li><a href="bank_login.php">Bank Portal</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Sylhet IT Shop. All rights reserved.</p>
        </div>
    </footer> 


	<script>
        $(document).ready(function () {
            
            // --- New Function: Update Cart Badge ---
            function updateCartBadge() {
                let total = 0;
                // Get quantity from each product and add to total
                total += parseInt($("#amount111").text()) || 0;
                total += parseInt($("#amount222").text()) || 0;
                total += parseInt($("#amount333").text()) || 0;
                
                let badge = $("#cart-badge");
                if (total > 0) {
                    badge.text(total).fadeIn(); // Show badge if items > 0
                } else {
                    badge.fadeOut(); // Hide badge if cart is empty
                }
            }

            // --- Your Original JS, Modified to Update Badge ---
            $("#dec111, #dec222, #dec333").click(function () {
                let id = this.id.replace("dec", "");
                let amountId = "#amount" + id;
                let priceId = "#price" + id;

                $.ajax({
                    method: "POST",
                    url: "decrement" + id + ".php",
                    data: { amount: $(amountId).text() },
                    success: function (response) {
                        let data = JSON.parse(response);
                        $(amountId).text(data.amount);
                        $(priceId).text(data.price);
                        updateCartBadge(); // <-- Update badge on success
                    }
                });
            });

            $("#inc111, #inc222, #inc333").click(function () {
                let id = this.id.replace("inc", "");
                let amountId = "#amount" + id;
                let priceId = "#price" + id;

                $.ajax({
                    method: "POST",
                    url: "increment" + id + ".php",
                    data: { amount: $(amountId).text() },
                    success: function (response) {
                        let data = JSON.parse(response);
                        $(amountId).text(data.amount);
                        $(priceId).text(data.price);
                        updateCartBadge(); // <-- Update badge on success
                    }
                });
            });

            $("#reset111, #reset222, #reset333").click(function () {
                let id = this.id.replace("reset", "");
                $("#price" + id).text(0);
                $("#amount" + id).text(0);
                updateCartBadge(); // <-- Update badge on reset
            });

            // --- Your Original Cart Button Logic (Unchanged) ---
            $("#open_cart").click(function () {
                let url = "cart.php?laptop=" + $("#amount111").text() + "&mobile=" + $("#amount222").text() + "&calculator=" + $("#amount333").text() +
                    "&lp=" + $("#price111").text() + "&mp=" + $("#price222").text() + "&cp=" + $("#price333").text();
                window.location = url;
            });
            
            // --- Run on page load ---
	    updateCartBadge();

	    // --- NEW: Modal Logic ---

        // 1. Store your product specifications here
        const productSpecs = {
            'laptop': {
                name: 'High-Performance Laptop',
                image: 'products/laptop.png',
                details: [
                    { spec: 'Processor', value: 'Intel Core i7, 12th Gen' },
                    { spec: 'RAM', value: '16GB DDR5' },
                    { spec: 'Storage', value: '1TB NVMe SSD' },
                    { spec: 'Display', value: '15.6" QHD 165Hz' },
                    { spec: 'Graphics', value: 'NVIDIA RTX 4060' }
                ]
            },
            'mobile': {
                name: 'Latest Smartphone',
                image: 'products/mobile.png',
                details: [
                    { spec: 'Processor', value: 'Snapdragon 8 Gen 2' },
                    { spec: 'RAM', value: '12GB LPDDR5X' },
                    { spec: 'Storage', value: '256GB UFS 4.0' },
                    { spec: 'Display', value: '6.7" AMOLED 120Hz' },
                    { spec: 'Camera', value: '200MP Main Sensor' }
                ]
            },
            'calculator': {
                name: 'Scientific Calculator',
                image: 'products/calculator.png',
                details: [
                    { spec: 'Type', value: 'Scientific' },
                    { spec: 'Functions', value: '417 Functions' },
                    { spec: 'Display', value: 'Natural Textbook Display' },
                    { spec: 'Power', value: 'Solar + Battery (LR44)' },
                    { spec: 'Color', value: 'Black' }
                ]
            }
        };

        // 2. Open Modal on Card Click
        $('.product-image').on('click', function() {
            // Get product data
            const productId = $(this).data('product');
            const specs = productSpecs[productId];

            // Populate modal
            $('#modalProductName').text(specs.name);
            $('#modalProductImage').attr('src', specs.image);

            // Populate specs table
            const specsTable = $('#modalProductSpecs');
            specsTable.empty(); // Clear old specs
            specs.details.forEach(item => {
                specsTable.append(`<tr><td>${item.spec}</td><td>${item.value}</td></tr>`);
            });

            // Show modal
            $('#productModal').css('display', 'flex').addClass('active');
            $('body').addClass('modal-open');
        });

        // 3. Close Modal Function
        function closeModal() {
            const modal = $('#productModal');
            modal.removeClass('active');
            $('body').removeClass('modal-open');
            // Wait for animation to finish before hiding
            setTimeout(() => {
                modal.css('display', 'none');
            }, 300); // 300ms matches CSS transition
        }

        // 4. Close Modal on Button Click
        $('#modalCloseButton').on('click', function() {
            closeModal();
        });

        // 5. Close Modal on Overlay Click
        $('#productModal').on('click', function(e) {
            // Check if the click is on the overlay itself, not the content
            if (e.target === this) {
                closeModal();
            }
        });
        });
    </script>
</body>

</html>

