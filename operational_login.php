<?php
// We only need this file to get the redirect error, if there is one.
$error = $_GET['error'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="js/jquery.min.js"></script>
    <title>Admin & Supplier Login</title>
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
            --light-gray-bg: #e9ecef;
            --light-gray-border: #ddd;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --gradient: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--gradient);
            margin: 0;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            box-sizing: border-box;
        }

        /* --- Login Card --- */
        .login-container {
            width: 100%;
            max-width: 420px;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 2.5rem;
            text-align: center;
        }
        .login-container h2 {
            color: var(--text-color);
            font-size: 1.8rem;
            margin-top: 0;
            margin-bottom: 0.5rem;
        }
        .login-container .subtitle {
            color: var(--text-light);
            margin-bottom: 2rem;
        }
        
        .login-form {
            text-align: left;
        }
        .input-group {
            margin-bottom: 1.5rem;
        }
        .input-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        /* --- Updated Input Styling --- */
        .input-group input[type="email"],
        .input-group input[type="password"],
        .input-group input[type="text"] { /* Added type="text" */
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
        
        /* * --- THE FIX ---
         * We now target the input by its ID, so the style
         * applies whether it is type="password" or type="text".
        */
        .input-group input#password {
            padding-right: 65px; 
        }

        .input-group input[type="email"]:focus,
        .input-group input[type="password"]:focus,
        .input-group input[type="text"]:focus { /* Added type="text" */
            outline: none;
            border-color: var(--primary-color);
        }

        /* --- Password Wrapper and Toggle Button --- */
        .password-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            padding: 5px;
            z-index: 10;
        }
        .toggle-password:hover {
            color: var(--primary-color);
        }


        /* --- Buttons --- */
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .button {
            display: block;
            width: 100%;
            padding: 15px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-submit {
            background: var(--gradient);
            color: white;
            background-size: 200% auto;
        }
        .btn-submit:hover {
            background-position: right center;
            box-shadow: 0 5px 15px rgba(74, 0, 224, 0.3);
        }
        
        .btn-clear {
            background: var(--light-gray-bg);
            color: var(--text-light);
            border: 2px solid var(--light-gray-bg);
        }
        .btn-clear:hover {
            background: #dfe3e6;
            color: var(--text-color);
            border-color: #dfe3e6;
        }
        
        .home-link {
            display: block;
            margin-top: 1.5rem;
            padding: 12px;
            color: var(--text-light);
            text-decoration: none;
            font-size: 1rem;
            font-weight: 600;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .home-link:hover {
            background: var(--bg-color);
            color: var(--primary-color);
            border-color: var(--bg-color);
        }
        
        /* Error Message */
        .error-message {
            background: var(--red-light-bg);
            border: 1px solid var(--red-light-border);
            color: var(--red-color);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

    </style>
        <link rel="icon" href="shop_icon.png" type="image/x-icon">

</head>
<body>
    <div class="login-container">
        <h2>Admin & Supplier Login</h2>
        <p class="subtitle">Log in to manage your operations.</p>

        <?php if ($error): ?>
            <div class="error-message">
                Invalid email or password. Please try again.
            </div>
        <?php endif; ?>

        <form action="auth.php" method="POST" class="login-form">
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" placeholder="Enter your email" required>
            </div>

            <div class="input-group">
                <label for="password">Password:</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                    <button type="button" id="togglePassword" class="toggle-password">Show</button>
                </div>
            </div>

            <div class="button-group">
                <input type="reset" name="clear" value="Clear" class="button btn-clear">
                <input type="submit" name="login" value="Login" class="button btn-submit">
            </div>
        </form>
        
        <a href="index.html" class="home-link">&larr; Back to Main Website</a>
    </div>

    <script>
    $(document).ready(function() {
        $('#togglePassword').on('click', function() {
            var passwordField = $('#password');
            var passwordFieldType = passwordField.attr('type');
            
            if (passwordFieldType === 'password') {
                passwordField.attr('type', 'text');
                $(this).text('Hide');
            } else {
                passwordField.attr('type', 'password');
                $(this).text('Show');
            }
        });
    });
    </script>
</body>
</html>
