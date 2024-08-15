<?php
// http://192.168.0.125/ecs/tools/claims_list.php

// http://localhost/claims/claims_list.php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
// set_time_limit(40);
// ini_set("memory_limit", "-1");

$db_path = '../db.sqlite3';
// $db_path = 'db.sqlite3'; //DEBUG

$order_ids_str = '';
$total_order_ids = 0;
if (isset($_POST['update_claims'])) {
    $db_ecs = new PDO("sqlite:$db_path");
    
    // Explode order_ids str to array
    $order_ids_str = trim($_POST['order_ids']);
    $order_ids = explode("\n", $order_ids_str);
    
    $order_ids = array_map(function($order_id){
        return trim($order_id);
    }, $order_ids);
    
    // Remove empty values from array
    $order_ids = array_filter($order_ids);
    
    $total_order_ids = count($order_ids);
    
    $tbl_name = 'order_claims';
    
    if( $total_order_ids ){
        $sql = "SELECT `form_id` FROM `$tbl_name` ORDER BY `id` DESC LIMIT 1";
        $results = $db_ecs->query($sql);
        $last_form_id_plus1 = $results->fetchAll(PDO::FETCH_COLUMN)[0] +1;
        
        $stmt = $db_ecs->prepare("INSERT INTO `$tbl_name` (`form_id`,`order_id`,`rejected`,`total`) VALUES (?,?,?,?)");
        $db_ecs->beginTransaction();
        foreach ($order_ids as $order_id) {
            $stmt->execute([ $last_form_id_plus1, $order_id, 0, 8 ]);
        }
        $db_ecs->commit();
    }
    
    $order_ids_str = '';
}

elseif (isset($_POST['view_order_ids'])) {
    $db_ecs = new PDO("sqlite:$db_path");

    $sql = "SELECT order_id,'1' FROM `order_claims`";
    $results = $db_ecs->query($sql);
    $order_claims = $results->fetchAll(PDO::FETCH_KEY_PAIR);

    $sql = "SELECT id,name FROM `order_option` WHERE `claimable` = 1";
    $results = $db_ecs->query($sql);
    $options_lkup = $results->fetchAll(PDO::FETCH_KEY_PAIR);
    /*
    Array
    (
        [2] => Lost - Part Tracking
        [3] => Damaged In Transit
        [4] => Arrived Damaged
        [6] => Partial Damage
        [16] => Damaged In Transit Scan
        [25] => Delivered Not Recieved
        [27] => damaged storage bucket
        [28] => claim no action needed
    )
     */

    $option_ids_str = implode("','", array_keys($options_lkup) );

    $sql = "SELECT `order_id`,`option_id` FROM `order_refund` WHERE `option_id` IN ('$option_ids_str')";
    $results = $db_ecs->query($sql);
    $order_refund_order_option_ids = $results->fetchAll(PDO::FETCH_ASSOC);

    // Set order_id as array key
    $order_refund_resend_order_option_ids = [];
    foreach( $order_refund_order_option_ids as $rec ){
        if( !isset($order_claims[ $rec['order_id'] ]) ){
            $order_refund_resend_order_option_ids[ $rec['order_id'] ] = $rec;
        }
    }

    $sql = "SELECT `order_id`,`option_id` FROM `order_resend` WHERE `option_id` IN ('$option_ids_str')";
    $results = $db_ecs->query($sql);
    $order_resend_order_option_ids = $results->fetchAll(PDO::FETCH_ASSOC);

    // Set order_id as array key
    foreach( $order_resend_order_option_ids as $rec ){
        if( !isset($order_claims[ $rec['order_id'] ]) ){
            $order_refund_resend_order_option_ids[ $rec['order_id'] ] = $rec;
        }
    }


    $claims = [];
    foreach( $order_refund_resend_order_option_ids as $orderID => $rec ){
        $claims[] = [ $options_lkup[ $rec['option_id'] ], $orderID ];
    }
    ksort($claims);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=Claims.csv');

    $csvHandle = fopen('php://output', 'w');
    fprintf($csvHandle, chr(0xEF).chr(0xBB).chr(0xBF)); // output utf-8 (stops £ becoming Â£)
    fputcsv($csvHandle, [
        'Status',
        'Order ID',
    ]);

    foreach ($claims as $claim) {
        fputcsv($csvHandle, [
            $claim[0],
            $claim[1],
        ]);
    }
    fclose($csvHandle);
    die();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Claims</title>

<!-- <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script> -->
<!-- <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script> -->
<!-- <link rel="stylesheet" href="style.css"> -->

<style>
    * {
        font-family: 'Nunito', tahoma, arial, sans-serif;
    }
    
    body {
        padding-top: 10px;
        padding-left: 20px;
    }
    
    textarea {
        width:300px;
        height: 600px;
    }
    
    button, input[type="submit"] {
        width: 200px;
        height: 34px;
        font-size: 16px;
        
        position: relative;
        display: inline-block;
        font-family: inherit;
        background: linear-gradient(#78a, #579);
        border-radius: 2px;
        box-shadow: 1px 1px 0 0 rgba(17, 68, 102, 1);
        padding: 4px 8px;
        margin-bottom: 1px;
        border: 1px solid #124;
        color: #fff;
        text-shadow: 1px 1px 1px rgba(50, 50, 50, 0.5);
        cursor: pointer;
    }
    
    button:hover, input[type="submit"]:hover {
        background: linear-gradient(#579, #78a);
    }
</style>

</head>
<body>

<div style="position: absolute; left: 370px; top: 70px; display: <?= $total_order_ids ? 'inline' : 'none'; ?>;">
    <h3><span style="font-size: 30px;"><?= $total_order_ids ?></span> order IDs have been added to the claims table.</h3>
</div>


<div style="float: left; margin-top: -20px; padding-right: 100px;">
    <h1>Add Order IDs to Claims</h1>

    <form method="post">
        <p><textarea name="order_ids" id="order_ids"><?= $order_ids_str ?></textarea></p>
        
        <input type="hidden" name="update_claims">
        <input type="submit" name="submit" value="Update Claims">
    </form>
    <script>document.getElementById("order_ids").focus();</script>
</div>


<form method="post" target="_blank">
    <input type="hidden" name="view_order_ids">
    <input type="submit" name="submit" value="View Claims">
</form>


<script>
$(function() {});
</script>
</body>
</html>