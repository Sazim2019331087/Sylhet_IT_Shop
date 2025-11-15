<?php 
require "config.php";
$sql7 = "SELECT * FROM product_details;";
$q7 = mysqli_query($con,$sql7);
$r7 = mysqli_fetch_assoc($q7);

echo "<pre>";
print_r($r7);
echo "<//pre>";

?>