<?php 
error_reporting(0);

	$hostname = "HOST_NAME";
	$user = "USER_NAME";
	$password = "PASSWORD";
	$database = "DATABASE_NAME";
	//$port = 3306;


	$con = mysqli_connect($hostname,$user,$password,$database);	
	
    /*
    if($con)
    {
        echo "connected";
    }
    else
    {
        echo "disconnected";
    }
    */
    
?>
