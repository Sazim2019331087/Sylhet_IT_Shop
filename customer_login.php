<?php
// We must start the session at the very top
session_start();
require "config.php";
require "local_time.php";

$error_message = ""; // Variable to hold our error message

if (isset($_POST["login_account"])) {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // --- CRITICAL SECURITY FIX 1: Use Prepared Statements ---
    $stmt1 = $con->prepare("SELECT * FROM customer_details WHERE email = ?");
    $stmt1->bind_param("s", $email);
    $stmt1->execute();
    $result = $stmt1->get_result();

    if ($result->num_rows === 1) {
        $details = $result->fetch_assoc();
        
        $hashed_password_from_db = $details["password"];

        // --- CRITICAL SECURITY FIX 2: Use password_verify() ---
        // This securely checks the user's password against the hash in the DB
        if (password_verify($password, $hashed_password_from_db)) {
            // Login Success!
            session_regenerate_id(true); // Protects against session fixation
            
            $_SESSION["email"] = $details["email"];
            $_SESSION["account_number"] = $details["account_number"];
            $_SESSION["secret"] = $details["secret"];
            $_SESSION["name"] = $details["name"];
            // We DO NOT store the plain-text password in the session.
            
            header("location:customer_profile.php");
            exit;
        } else {
            // Password was incorrect
            $error_message = "The password you entered is incorrect. Please try again!";
        }
    } else {
        // No account found
        $error_message = "No account was found with that email address.";
    }
    $stmt1->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="js/jquery.min.js"></script>
    <title>Login - Sylhet IT Shop</title>
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
            max-width: 450px;
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
        
        .form-container {
            text-align: left;
        }
        .input-group {
            margin-bottom: 1.25rem;
        }
        .input-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .input-group input[type="email"],
        .input-group input[type="password"],
        .input-group input[type="text"] {
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
        .input-group input#password {
            padding-right: 65px; /* Make room for show/hide button */
        }
        .input-group input[type="email"]:focus,
        .input-group input[type="password"]:focus,
        .input-group input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Password Wrapper and Toggle Button */
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

        /* Buttons */
        .form-actions {
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
        .btn-reset {
            background: var(--light-gray-bg);
            color: var(--text-light);
            border: 2px solid var(--light-gray-bg);
        }
        .btn-reset:hover {
            background: #dfe3e6;
            color: var(--text-color);
            border-color: #dfe3e6;
        }
        
        .signup-link {
            display: block;
            margin-top: 1.5rem;
            padding: 12px;
            color: var(--text-light);
            text-decoration: none;
            font-size: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .signup-link:hover {
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
        <h2>Login Account</h2>
        <p class="subtitle">Welcome back! Please login to your account.</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="customer_login.php" method="POST" class="form-container">
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>

            <div class="input-group">
                <label for="password">Password:</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <button type="button" id="togglePassword" class="toggle-password">Show</button>
                </div>
            </div>

            <div class="form-actions">
                <input type="reset" name="reset" value="Reset" class="button btn-reset">
                <input type="submit" name="login_account" value="Login" class="button btn-submit">
            </div>
        </form>
        
        <a href="customer_sign_up.php" class="signup-link">Don't have an account? Create one.</a>
        <a href="forgot_password.php" class="signup-link"style="color:red;">Forgot Password?</a>
		
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
