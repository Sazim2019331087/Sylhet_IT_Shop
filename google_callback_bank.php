<?php
session_start();
require "config.php";
require "google_config_bank.php"; // Use the BANK config

if (isset($_GET['code'])) {
    // 1. Exchange code for Token
    $token = $client_bank->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
        $client_bank->setAccessToken($token['access_token']);

        // 2. Get User Profile
        $google_oauth = new Google_Service_Oauth2($client_bank);
        $google_account_info = $google_oauth->userinfo->get();

        $email = $google_account_info->email;
        $name = $google_account_info->name;
        $google_id = $google_account_info->id;

        // 3. Check if user exists in BANK_DETAILS
        $stmt = $con->prepare("SELECT * FROM bank_details WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // --- SCENARIO A: Bank Account Exists ---
            $user = $result->fetch_assoc();
            
            // Update google_id if missing
            if (empty($user['google_id'])) {
                $update = $con->prepare("UPDATE bank_details SET google_id = ? WHERE email = ?");
                $update->bind_param("ss", $google_id, $email);
                $update->execute();
            }
            
            // Login
            // Note: Bank session logic might differ slightly from Customer logic
            // Usually you don't store bank sessions mixed with shop sessions, 
            // but for this project structure, it is fine.
            
            // Redirect to Bank Profile with account number
            $acc_num = $user["account_number"];
            header("Location: bank_profile.php?account_number=$acc_num");
            exit;

        } else {
            // --- SCENARIO B: New Bank User (Auto-Open Account) ---
            
            // 1. Generate unique Account Number (Same logic as bank_sign_up.php)
            $account_number = mt_rand(10000, 90000);
            
            // 2. Give Starting Balance
            $current_balance = 50000;
            
            // 3. Insert into Database (Password is NULL)
            $stmt_insert = $con->prepare("INSERT INTO bank_details (email, name, password, account_number, current_balance, google_id) VALUES (?, ?, NULL, ?, ?, ?)");
            $stmt_insert->bind_param("sssis", $email, $name, $account_number, $current_balance, $google_id);
            
            if ($stmt_insert->execute()) {
                // Redirect to Bank Profile
                header("Location: bank_profile.php?account_number=$account_number");
                exit;
            } else {
                die("Database Error: Could not open bank account.");
            }
        }
    } else {
        header("Location: bank_login.php");
        exit;
    }
} else {
    header("Location: bank_login.php");
    exit;
}
?>
