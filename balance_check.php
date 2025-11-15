<?php
require "config.php";
$acc_number = $_POST["account_number"];
//echo $acc_number;
$sql = "SELECT * FROM bank_details WHERE account_number = '$acc_number'";
$q = mysqli_query($con, $sql);
$r = mysqli_fetch_assoc($q);
echo $r["current_balance"];
?>