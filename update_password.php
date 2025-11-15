<?php
session_start();
require "config.php";

$error_message = "";
$success_message = "";

// SECURITY CHECK: Only allow access if OTP was verified
if (!isset($_SESSION['can_update_password'])) {
    die("You do not have permission to access this page. Please start the password reset process from the beginning.");
}

$email = $_SESSION['can_update_password'];

// --- Handle the password update ---
if (isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        // Validation passed
        
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Securely update the password in customer_details
        $stmt_update = $con->prepare("UPDATE customer_details SET password = ? WHERE email = ?");
        $stmt_update->bind_param("ss", $hashed_password, $email);
        
        if ($stmt_update->execute()) {
            $success_message = "Password updated successfully! You will be redirected to the login page in 3 seconds.";
            
            // Clear the session flags to prevent reuse
            unset($_SESSION['can_update_password']);
            unset($_SESSION['otp_email']);
            
            // Redirect to login after 3 seconds
            header("refresh:3;url=customer_login.php");
        } else {
            $error_message = "An error occurred. Please try again.";
        }
        $stmt_update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password - Sylhet IT Shop</title>
    <style>
        /* (Using the same CSS as forgot_password.php for consistency) */
        :root {
            --primary-color: #4a00e0;
            --secondary-color: #8e2de2;
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333;
            --text-light: #555;
            --red-color: #e74c3c;
            --green-color: #2ecc71;
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
        }
        .form-container {
            width: 100%;
            max-width: 450px;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 2.5rem;
            text-align: center;
        }
        h2 { font-size: 1.8rem; margin-top: 0; }
        .subtitle { color: var(--text-light); margin-bottom: 2rem; }
        .input-group { text-align: left; margin-bottom: 1.5rem; }
        .input-group label { font-weight: 600; margin-bottom: 0.5rem; display: block; }
        .input-group input[type="password"] {
            width: 100%; padding: 14px; border: none;
            background-color: var(--bg-color); border-radius: 8px;
            font-size: 1rem; box-sizing: border-box;
            border: 2px solid var(--bg-color);
        }
        .input-group input[type="password"]:focus { outline: none; border-color: var(--primary-color); }
        .button {
            display: block; width: 100%; padding: 15px; font-size: 1rem;
            font-weight: 600; border: none; border-radius: 8px;
            cursor: pointer; text-decoration: none; transition: all 0.3s ease;
            background: var(--gradient); color: white;
        }
        .button:hover { box-shadow: 0 5px 15px rgba(74, 0, 224, 0.3); }
        .message {
            padding: 1rem; border-radius: 8px; font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .success { background: #eafaf1; color: var(--green-color); }
        .error { background: #fdeded; color: var(--red-color); }
    </style>
            <link rel="icon" href="shop_icon.png" type="image/x-icon">

</head>
<body>
    <div class="form-container">
        <h2>Set New Password</h2>

        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (empty($success_message)): ?>
            <p class="subtitle">Create a new, strong password for your account.</p>
            <form action="update_password.php" method="POST">
                <input type="hidden" name="action" value="update_password">
                <div class="input-group">
                    <label for="password">New Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="input-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="update_password_button" class="button">Update Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
