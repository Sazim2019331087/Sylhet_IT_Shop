<?php
require_once 'vendor/autoload.php';

// 1. Init Google Client for the BANK
$client_bank = new Google_Client();
$client_bank->setClientId('CLIENT_ID'); 
$client_bank->setClientSecret('CLIENT_SECRET'); 

// ** CRITICAL: Note the different filename for the callback **
$client_bank->setRedirectUri('https://sylhetitshop.zya.me/google_callback_bank.php');

// 2. Define permissions
$client_bank->addScope("email");
$client_bank->addScope("profile");
?>
