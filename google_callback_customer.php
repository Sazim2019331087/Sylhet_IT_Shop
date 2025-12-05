<?php
session_start();
require "config.php";
require "google_config_customer.php";

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);

        // Get Profile Info
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();

        $email = $google_account_info->email;
        $name = $google_account_info->name;
        $google_id = $google_account_info->id;

        // Check Database
        $stmt = $con->prepare("SELECT * FROM customer_details WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // --- User Exists: Login ---
            $user = $result->fetch_assoc();
            
            // Sync google_id if missing
            if (empty($user['google_id'])) {
                $update = $con->prepare("UPDATE customer_details SET google_id = ? WHERE email = ?");
                $update->bind_param("ss", $google_id, $email);
                $update->execute();
            }

            // Set Session
            $_SESSION["email"] = $user["email"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["account_number"] = $user["account_number"];
            $_SESSION["secret"] = $user["secret"];

            header("Location: customer_profile.php");
            exit;

        } else {
            // --- New User: Register ---
            // Default values for a new customer
            $account_number = "NOT SET";
            $secret = "NOT SET";

            $stmt_insert = $con->prepare("INSERT INTO customer_details (email, name, password, account_number, secret, google_id) VALUES (?, ?, NULL, ?, ?, ?)");
            $stmt_insert->bind_param("sssss", $email, $name, $account_number, $secret, $google_id);

            if ($stmt_insert->execute()) {
                $_SESSION["email"] = $email;
                $_SESSION["name"] = $name;
                $_SESSION["account_number"] = $account_number;
                $_SESSION["secret"] = $secret;
                
                header("Location: customer_profile.php");
                exit;
            } else {
                die("Database Error: Registration failed.");
            }
        }
    }
}
header("Location: customer_login.php");
exit;
?>