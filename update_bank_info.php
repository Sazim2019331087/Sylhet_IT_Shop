<?php
session_start();
require "config.php";
require "smtp_mailer_bank.php"; // <-- THIS IS THE ONLY CHANGE

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: customer_login.php");
    exit;
}

$email = $_SESSION['email']; // The customer's e-commerce email
$error_message = "";
$success_message = "";
$show_otp_form = false;

// --- STAGE 1: User submits their bank info and password ---
if (isset($_POST['action']) && $_POST['action'] === 'send_otp') {
    
    $new_account_number = $_POST['new_account_number'];
    $new_secret = $_POST['new_secret'];
    $login_password = $_POST['login_password'];

    // 1. Verify ownership of the E-COMMERCE account (check their login password)
    $stmt_check_pass = $con->prepare("SELECT password FROM customer_details WHERE email = ?");
    $stmt_check_pass->bind_param("s", $email);
    $stmt_check_pass->execute();
    $user = $stmt_check_pass->get_result()->fetch_assoc();
    $stmt_check_pass->close();

    if (!$user || !password_verify($login_password, $user['password'])) {
        $error_message = "Your e-commerce login password was incorrect.";
    } else {
        // 2. E-commerce password is correct. Now, verify the BANK account.
        $stmt_check_bank = $con->prepare("SELECT email FROM bank_details WHERE account_number = ?");
        $stmt_check_bank->bind_param("s", $new_account_number);
        $stmt_check_bank->execute();
        $bank_user = $stmt_check_bank->get_result()->fetch_assoc();
        $stmt_check_bank->close();

        if (!$bank_user) {
            $error_message = "The bank account number you entered does not exist.";
        } else {
            // 3. Bank account exists. Send OTP to the bank account's email.
            $bank_email = $bank_user['email'];
            $otp = mt_rand(100000, 999999);
            $expires = time() + 600; // 10 minutes

            // Store OTP in the main `otp_verification` table
            $stmt_otp = $con->prepare("INSERT INTO otp_verification (email, otp, expires) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE otp = ?, expires = ?");
            $stmt_otp->bind_param("ssisi", $bank_email, $otp, $expires, $otp, $expires);
            $stmt_otp->execute();
            $stmt_otp->close();

            // Send the email
            $html_msg = "<h2>Sylhet IT Shop - Bank Account Verification</h2>
                         <p>Someone (hopefully you) is trying to link this bank account to their Sylhet IT Shop account.</p>
                         <p>Your One-Time Password (OTP) is: <b>$otp</b></p>
                         <p>If this was not you, please secure your bank account immediately.</p>";
            
            // This now calls the function from smtp_mailer_bank.php
            if (smtp_mailer($bank_email, "Verify Your Bank Account Linking", $html_msg)) {
                $success_message = "An OTP has been sent to the email address associated with your bank account.";
                $show_otp_form = true;
                
                // Store data to be saved *after* OTP verification
                $_SESSION['pending_bank_acct'] = $new_account_number;
                $_SESSION['pending_bank_secret'] = $new_secret;
                $_SESSION['pending_bank_email'] = $bank_email; // Store the email we sent the OTP to
            } else {
                $error_message = "Could not send verification email. Please try again later.";
            }
        }
    }
}

// --- STAGE 2: User submits the OTP ---
if (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
    $otp_entered = $_POST['otp'];
    $bank_email = $_SESSION['pending_bank_email'] ?? null;

    if ($bank_email === null) {
        $error_message = "Your session expired. Please start over.";
    } else {
        // Securely check the OTP
        $stmt_verify = $con->prepare("SELECT otp FROM otp_verification WHERE email = ? AND expires > ?");
        $current_time = time();
        $stmt_verify->bind_param("si", $bank_email, $current_time);
        $stmt_verify->execute();
        $result = $stmt_verify->get_result();

        if ($result->num_rows === 1 && $result->fetch_assoc()['otp'] === $otp_entered) {
            // SUCCESS! OTP is correct.
            $new_account_number = $_SESSION['pending_bank_acct'];
            $new_secret = $_SESSION['pending_bank_secret'];

            // Now, finally update the customer_details table
            $stmt_update = $con->prepare("UPDATE customer_details SET account_number = ?, secret = ? WHERE email = ?");
            $stmt_update->bind_param("sss", $new_account_number, $new_secret, $email); // $email is the e-commerce email
            $stmt_update->execute();

            // Update session
            $_SESSION['account_number'] = $new_account_number;
            $_SESSION['secret'] = $new_secret;

            // Clean up
            $stmt_del = $con->prepare("DELETE FROM otp_verification WHERE email = ?");
            $stmt_del->bind_param("s", $bank_email);
            $stmt_del->execute();
            unset($_SESSION['pending_bank_acct'], $_SESSION['pending_bank_secret'], $_SESSION['pending_bank_email']);

            $success_message = "Bank account linked successfully! Redirecting to your profile...";
            header("refresh:3;url=customer_profile.php");

        } else {
            $error_message = "Invalid or expired OTP. Please try again.";
            $show_otp_form = true; // Let them try again
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Payment Info - Sylhet IT Shop</title>
    <script src="js/jquery.min.js"></script>
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
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
        }
        .navbar {
            background: var(--card-bg);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 0 2rem;
            position: sticky; top: 0; z-index: 100;
        }
        .navbar-container {
            display: flex; justify-content: space-between; align-items: center;
            max-width: 1200px; margin: 0 auto; height: 70px;
        }
        .navbar-brand {
            font-size: 1.5rem; font-weight: 700; background: var(--gradient);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            text-decoration: none;
        }
        .navbar-links { list-style: none; margin: 0; padding: 0; }
        .navbar-links a {
            text-decoration: none; color: var(--text-light); font-weight: 600;
            padding: 8px 16px; border-radius: 6px; transition: all 0.3s ease;
        }
        .navbar-links a:hover { background: var(--bg-color); color: var(--primary-color); }
        .form-container-wrapper {
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 110px);
        }
        .form-container {
            width: 100%;
            max-width: 500px;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 2.5rem;
        }
        h2 { font-size: 1.8rem; margin-top: 0; text-align: center; }
        .subtitle { color: var(--text-light); margin-bottom: 2rem; text-align: center;}
        .input-group { margin-bottom: 1.5rem; }
        .input-group label { font-weight: 600; margin-bottom: 0.5rem; display: block; }
        .input-group input[type="text"],
        .input-group input[type="password"] {
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
        .message {
            padding: 1rem; border-radius: 8px; font-weight: 600;
            margin-bottom: 1.5rem; text-align: center;
        }
        .success { background: #eafaf1; color: var(--green-color); }
        .error { background: #fdeded; color: var(--red-color); }
    </style>
            <link rel="icon" href="bank_icon.png" type="image/x-icon">

</head>
<body>
    <div class="navbar">
        <div class="navbar-container">
            <a href="market.php" class="navbar-brand">Sylhet IT Shop</a>
            <ul class="navbar-links">
                <li><a href="customer_profile.php">&larr; Cancel</a></li>
            </ul>
        </div>
    </div>

    <div class="form-container-wrapper">
        <div class="form-container">
            
            <?php if (!empty($error_message)): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($show_otp_form): ?>
                <h2>Verify Ownership</h2>
                <p class="subtitle">An OTP was sent to the email address on file with your bank. Please enter it below.</p>
                <form action="update_bank_info.php" method="POST">
                    <input type="hidden" name="action" value="verify_otp">
                    <div class="input-group">
                        <label for="otp">OTP Code:</label>
                        <input type="text" id="otp" name="otp" required>
                    </div>
                    <button type="submit" name="verify_button" class="button">Verify & Link Account</button>
                </form>

            <?php elseif (empty($success_message)): ?>
                <h2>Update Payment Information</h2>
                <p class="subtitle">Your identity will be verified in two steps.</p>
                <form action="update_bank_info.php" method="POST">
                    <input type="hidden" name="action" value="send_otp">
                    <div class="input-group">
                        <label for="new_account_number">New Bank Account Number:</label>
                        <input type="text" id="new_account_number" name="new_account_number" required>
                    </div>
                    <div class="input-group">
                        <label for="new_secret">New Transaction Secret (PIN):</label>
                        <input type="password" id="new_secret" name="new_secret" required>
                    </div>
                    <hr style="border:0; border-top:1px solid #eee; margin: 1.5rem 0;">
                    <div class="input-group">
                        <label for="login_password">Confirm with your E-Commerce Password:</label>
                        <input type="password" id="login_password" name="login_password" required>
                    </div>
                    <button type="submit" name="update_payment" class="button">Send Verification OTP</button>
                </form>
            <?php endif; ?>
            
        </div>
    </div>
</body>
</html>
