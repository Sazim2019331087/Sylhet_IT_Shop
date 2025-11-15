<?php
session_start();
require "config.php";
require "smtp_mailer.php"; // Include your mailer function

$error_message = "";
$success_message = "";
$show_otp_field = false;

// --- STAGE 1: User submits their email ---
if (isset($_POST['action']) && $_POST['action'] === 'send_otp') {
    $email = $_POST['email'];

    // Securely check if email exists in customer_details
    $stmt_check = $con->prepare("SELECT email FROM customer_details WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 1) {
        // Email exists, generate OTP
        $otp = mt_rand(100000, 999999);
        $expires = time() + 600; // OTP expires in 10 minutes

        // Securely store OTP in the database
        $stmt_otp = $con->prepare("
            INSERT INTO otp_verification (email, otp, expires) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE otp = ?, expires = ?
        ");
        $stmt_otp->bind_param("ssisi", $email, $otp, $expires, $otp, $expires);
        $stmt_otp->execute();
        $stmt_otp->close();

        // Send the email
        $html_msg = "<h2>Sylhet IT Shop Password Reset</h2>
                     <p>Your One-Time Password (OTP) is: <b>$otp</b></p>
                     <p>This code is valid for 10 minutes.</p>";
        
        if (smtp_mailer($email, "Your Password Reset OTP", $html_msg)) {
            $success_message = "An OTP has been sent to your email.";
            $show_otp_field = true;
            $_SESSION['otp_email'] = $email; // Store email for verification step
        } else {
            $error_message = "Could not send email. Please try again later.";
        }
    } else {
        $error_message = "This email is not registered with an account.";
    }
    $stmt_check->close();
}

// --- STAGE 2: User submits the OTP ---
if (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
    $otp_entered = $_POST['otp'];
    $email = $_SESSION['otp_email'] ?? null;

    if ($email === null) {
        $error_message = "Your session expired. Please start over.";
    } else {
        // Securely check the OTP
        $stmt_verify = $con->prepare("SELECT otp FROM otp_verification WHERE email = ? AND expires > ?");
        $current_time = time();
        $stmt_verify->bind_param("si", $email, $current_time);
        $stmt_verify->execute();
        $result = $stmt_verify->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $correct_otp = $row['otp'];

            if ($otp_entered === $correct_otp) {
                // SUCCESS!
                // Delete the OTP so it can't be used again
                $stmt_del = $con->prepare("DELETE FROM otp_verification WHERE email = ?");
                $stmt_del->bind_param("s", $email);
                $stmt_del->execute();
                
                // Set a session variable to allow password update
                $_SESSION['can_update_password'] = $email;
                header("Location: update_password.php");
                exit;
            } else {
                $error_message = "Invalid OTP. Please try again.";
                $show_otp_field = true; // Keep showing the OTP field
            }
        } else {
            $error_message = "Your OTP has expired. Please request a new one.";
            // $show_otp_field remains false, showing the email field again
        }
        $stmt_verify->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Sylhet IT Shop</title>
    <style>
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
        .input-group input {
            width: 100%; padding: 14px; border: none;
            background-color: var(--bg-color); border-radius: 8px;
            font-size: 1rem; box-sizing: border-box;
            border: 2px solid var(--bg-color);
        }
        .input-group input:focus { outline: none; border-color: var(--primary-color); }
        .button {
            display: block; width: 100%; padding: 15px; font-size: 1rem;
            font-weight: 600; border: none; border-radius: 8px;
            cursor: pointer; text-decoration: none; transition: all 0.3s ease;
            background: var(--gradient); color: white;
        }
        .button:hover { box-shadow: 0 5px 15px rgba(74, 0, 224, 0.3); }
        .login-link {
            display: block; margin-top: 1.5rem; color: var(--text-light);
            text-decoration: none; font-size: 0.9rem;
        }
        .login-link:hover { text-decoration: underline; }
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
        
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($show_otp_field): ?>
            <h2>Check Your Email</h2>
            <p class="subtitle">Enter the 6-digit OTP we sent to <?php echo htmlspecialchars($_SESSION['otp_email']); ?>.</p>
            <form action="forgot_password.php" method="POST">
                <input type="hidden" name="action" value="verify_otp">
                <div class="input-group">
                    <label for="otp">OTP Code:</label>
                    <input type="text" id="otp" name="otp" required>
                </div>
                <button type="submit" name="verify_otp_button" class="button">Verify OTP</button>
            </form>
        <?php else: ?>
            <h2>Forgot Password</h2>
            <p class="subtitle">Enter your email to receive a reset OTP.</p>
            <form action="forgot_password.php" method="POST">
                <input type="hidden" name="action" value="send_otp">
                <div class="input-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
                </div>
                <button type="submit" name="send_otp_button" class="button">Send OTP</button>
            </form>
        <?php endif; ?>
        
        <a href="customer_login.php" class="login-link">&larr; Back to Login</a>
    </div>
</body>
</html>
