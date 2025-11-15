<?php
    $amount = $_POST["amount"];
    if($amount>0)
    {
        $amount = (int)$amount-1;
    }
    else
    {
        $amount = 0;
    }
    $price = (int)$amount*1000;
    echo json_encode(["amount"=>$amount,"price"=>$price]);
?>