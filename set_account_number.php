<?php 
require "config.php";
session_start();
$email = $_SESSION["email"];
$account_number = $_POST["account_number"];
$secret = $_POST["secret"];
$sql = "UPDATE customer_details SET account_number='$account_number', secret='$secret' WHERE email='$email'";
$q = mysqli_query($con,$sql);
$_SESSION["account_number"] = $account_number;
$_SESSION["secret"] = $secret;
echo $account_number;
//print_r($_SESSION);
?>