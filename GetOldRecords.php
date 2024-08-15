<?php

ini_set('memory_limit', '8080M');

$tripler = new PDO('sqlite:tripleR.db3');
$newDb = new PDO('sqlite:db.sqlite3');
$barcodeDb = new PDO('sqlite:orders.db3');
$apiOrdersDb = new PDO('sqlite:api_orders.db3');

$sql = "SELECT * FROM refunds";
$refunds = $tripler->query($sql);
$refunds = $refunds->fetchAll(PDO::FETCH_ASSOC);

$contactLookup = [];
foreach (['amazon_orders', 'ebay_orders', 'website_orders', 'onbuy_orders', 'ebay_prosalt_orders', 'floorworld_orders'] as $table) {
    $sql = "SELECT orderId, email FROM $table";
    $result = $apiOrdersDb->query($sql);
    $result = $result->fetchAll(PDO::FETCH_KEY_PAIR);

    $contactLookup = $contactLookup + $result;
}

$sql = "SELECT orderID, barcode, courier FROM orders";
$barcodeOrders = $barcodeDb->query($sql);
$barcodeOrders = $barcodeOrders->fetchAll(PDO::FETCH_ASSOC);

$tmp = [];
foreach ($barcodeOrders as $order) {
    $tmp[$order['orderID']] = $order;
}
$barcodeOrders = $tmp;


// Get source lookup from new database
$sql = "SELECT * FROM order_source";
$sourceLookup = $newDb->query($sql);
$sourceLookup = array_flip($sourceLookup->fetchAll(PDO::FETCH_KEY_PAIR));

// Get courier lookup from new database
$sql = "SELECT * FROM order_courier";
$courierLookup = $newDb->query($sql);
$courierLookup = array_flip($courierLookup->fetchAll(PDO::FETCH_KEY_PAIR));

// Get channel lookup from new database


$oldOrderRecords = [];
$oldRefundRecords = [];

foreach ($refunds as $order) {
    $source = trim(strtok($order['name'], '('));
    $channel = trim(strtok(')'));
    $name = strtok($order['name'], '-');
    $name = trim(strtok('-'));
    // Need to manually change these to match a group of reasons we decide on, same for courier
    // $courier = $courierLookup[ucfirst($barcodeOrders[$order['orderID']]['courier'])];
    // $reason = trim(strtok($order['refund_reason'], '-'));
    // $source_id = $sourceLookup[$source];
    $full_refund = false;
    if ($order['full_refund'] == 'y' || $order['full_refund'] == true) {
        $full_refund = true;
    }
    $refund_amount = 0;
    if (is_numeric($order['refund_amount'])) {
        $refund_amount = $refund_amount;
    }
    $void = false;
    if ($order['void'] == 'y' || $order['void'] == true) {
        $void = true;
    }

    $contact = null;
    if (isset($contactLookup[$order['orderID']])) {
        $contact = $contactLookup[$order['orderID']];
    }

    // Repalace / to make the conversion consistent
    $dateString = str_replace('/', '-', $order['date']);
    $date = new DateTime($dateString);
    $date = $date->format('Y-m-d');

    $oldOrderRecords[$order['orderID']] = [
        'order_id' => $order['orderID'],
        'tracking_id' => $order[' trackingID'],
        'name' => $name,
        'contact' => $contact,
        'date' => $date,
        'reason_notes' => $order['notes'],
        'courier_id' => $courier,
        'reason_id' => ' ',
        'source_id' => $source_id,
    ];

    $oldRefundRecords[$order['orderID']] = [
        'order_ptr_id' => $order['orderID'],
        'full_refund' => $full_refund,
        'refund_amount' => $refund_amount,
        'void' => $void,
        'dor' => null,
    ];
}


// DEBUG
echo '<pre style="background: black; color: white;">'; print_r($oldOrderRecords); echo '</pre>'; die();


// Do the same for resends and returns etc
// hermes stuff might be worth starting fresh
