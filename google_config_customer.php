<?php
require_once 'vendor/autoload.php';

// 1. Init Google Client
$client = new Google_Client();
$client->setClientId('CLIENT_ID');
$client->setClientSecret('CLIENT_SECRET');

// ** CRITICAL: This must match the URI you set in Google Cloud Console **
$client->setRedirectUri('https://sylhetitshop.zya.me/google_callback_customer.php');

$client->addScope("email");
$client->addScope("profile");
?>