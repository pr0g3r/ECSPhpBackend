<?php

$lookups = [
    'order_action_customer' => [
        1 => 'Refunded',
        2 => 'Messaged',
        3 => 'Replacement',
        4 => 'Part Refund',
    ],
    'order_action_product' => [
        1 => 'Restocked',
        2 => 'Salvaged',
        3 => 'Replaced',
        4 => 'Part Restock',
    ],
    'order_reason' => [
        1 => 'Courier',
        2 => 'Customer',
        3 => 'Pre-Dispatch',
        4 => 'Other',
    ],
    'order_option' => [
        1 => 'No Tracking',
        2 => 'Undelivered',
        3 => 'Damaged In Transit',
        4 => 'No Reason',
    ],
    'order_room' => [
        1 => 'Middle',
        2 => 'Bottom',
        3 => 'Top',
        4 => 'Right',
    ],
    'order_warehouse' => [
        1 => 'Jonh Doe',
        2 => 'Steve Doe',
        3 => 'Jack Doe',
        4 => 'Fred Doe',
    ],
    'source_source' => [
        1 => 'Amazon',
        2 => 'Ebay',
        3 => 'Website',
        4 => 'Onbuy',
    ],
    'courier_courier' => [
        1 => 'HI',
        2 => 'BH',
        3 => 'DE',
        4 => 'CR',
    ],
];

$order_reason_option = [
    1 => [1, 1],
    2 => [1, 2],
    3 => [1, 3],
    4 => [4, 1],

];

$tripler = new PDO('sqlite:tripleR.db3');
$newDb = new PDO('sqlite:db.sqlite3');

foreach ($lookups as $table => $vals) {
    $stmt = $newDb->prepare("INSERT INTO $table VALUES(?,?)");
    $newDb->beginTransaction();

    foreach ($vals as $index => $val) {
        $stmt->execute([$index, $val]);
    }

    $newDb->commit();
}

// Insert order reason options
$stmt = $newDb->prepare("INSERT INTO order_reason_option VALUES (?,?,?)");
$newDb->beginTransaction();
foreach ($order_reason_option as $index => $vals) {
    $stmt->execute([$index, $vals[0], $vals[1]]);
}
$newDb->commit();

function formatDate($date)
{
    $dateString = str_replace('/', '-', $date);
    $date = new DateTime($dateString);
    $date = $date->format('Y-m-d');

    return $date;
}

function nameSourceChannel($input)
{
    $result = [];
    $result['source'] = trim(strtok($input, '('));
    $result['channel'] = trim(strtok(')'));
    $result['name'] = strtok($input, '-');
    $result['name'] = trim(strtok('-'));


    return $result;
}

function isTrue($arg)
{
    if ($arg == 'y' || $arg == true || $arg == '1') {
        return 1;
    }

    return 0;
}

$newOrderRecords = [];
$newTypeRecords = [];

// ---------------------------------------------------------------------------

// Build test order / refund records

$sql = "SELECT * FROM refunds LIMIT 500";
$testRefunds = $tripler->query($sql);
$testRefunds = $testRefunds->fetchAll(PDO::FETCH_ASSOC);

foreach ($testRefunds as $order) {
    $tracking_id = strlen(trim($order['trackingID'])) ? trim($order['trackingID']) : null;

    $nameSourceChannel = nameSourceChannel($order['name']);
    $full_refund = isTrue($order['full_refund']);
    $refund_amount = is_numeric($order['refund_amount']) ? $order['refund_amount'] : 0;
    $void = isTrue($order['void']);
    $contact = str_replace(' ', '', $nameSourceChannel['name']) . '@live.co.uk';
    $date = formatDate($order['date']);

    $notes = strlen(trim($order['notes'])) ? trim($order['notes']) : null;

    $newOrderRecords[$order['orderID']] = [
        'order_id' => $order['orderID'],
        'source_id' => rand(1, 4),
        'courier_id' => rand(1, 4),
        'tracking_id' => $tracking_id,
        'date' => $date,
        'name' => $nameSourceChannel['name'],
        'contact' => $contact,
    ];

    $newTypeRecords['refund'][$order['orderID']] = [
        'order_id' => $order['orderID'],
        'created' => date('Y-m-d', mt_rand(1626944781, 1632215181)),
        'reason_id' => rand(1, 4),
        'notes' => $notes,
        'full_refund' => $full_refund,
        'amount' => $refund_amount,
        'void' => $void,
        'dor' => rand(0, 1),
        'processed' => null,
        'user_id' => null,
    ];
}

// ---------------------------------------------------------------------------

// Build test order / resend records

$sql = "SELECT * FROM resends LIMIT 500";
$testResneds = $tripler->query($sql);
$testResneds = $testResneds->fetchAll(PDO::FETCH_ASSOC);

foreach ($testResneds as $order) {
    $tracking_id = strlen(trim($order['trackingID'])) ? trim($order['trackingID']) : null;
    $nameSourceChannel = nameSourceChannel($order['name']);
    $date = formatDate($order['date']);
    $contact = str_replace(' ', '', $nameSourceChannel['name']) . '@live.co.uk';
    $room = rand(1, 4);
    $picked = rand(1, 4);
    $packed = rand(1, 4);

    $newOrderRecords[$order['orderID']] = [
        'order_id' => $order['orderID'],
        'source_id' => rand(1, 4),
        'courier_id' => rand(1, 4),
        'tracking_id' => $tracking_id,
        'date' => $date,
        'name' => $nameSourceChannel['name'],
        'contact' => $contact,
    ];

    $newTypeRecords['resend'][$order['orderID']] = [
        'order_id' => $order['orderID'],
        'created' => date('Y-m-d', mt_rand(1626944781, 1632215181)),
        'reason_id' => rand(1, 4),
        'notes' => $notes,
        'room_id' => $room,
        'picked_id' => $picked,
        'packed_id' => $packed,
        'dor' => rand(0, 1),
        'processed' => null,
        'user_id' => null,
    ];
}

// ---------------------------------------------------------------------------

// Build test order / return records

$sql = "SELECT * FROM returns LIMIT 500";
$testReturns = $tripler->query($sql);
$testReturns = $testReturns->fetchAll(PDO::FETCH_ASSOC);

foreach ($testReturns as $order) {
    $tracking_id = null;
    $nameSourceChannel = nameSourceChannel($order['name']);
    $contact = str_replace(' ', '', $nameSourceChannel['name']) . '@live.co.uk';
    $notes = strlen(trim($order['notes'])) ? trim($order['notes']) : null;
    $date = formatDate($order['return_date']);

    $newOrderRecords[$order['orderID']] = [
        'order_id' => $order['orderID'],
        'source_id' => rand(1, 4),
        'courier_id' => rand(1, 4),
        'tracking_id' => $tracking_id,
        'date' => $date,
        'name' => $nameSourceChannel['name'],
        'contact' => $contact,
    ];

    $newTypeRecords['return'][$order['orderID']] = [
        'order_id' => $order['orderID'],
        'created' => date('Y-m-d', mt_rand(1626944781, 1632215181)),
        'notes' => $notes,
        'reason_option_id' => rand(1, 4),
        'action_customer_id' => rand(1, 4),
        'action_product_id' => rand(1, 4),
        'processed' => null,
        'user_id' => null,
    ];
}

// ---------------------------------------------------------------------------

// Inser test order records

$sql = "PRAGMA table_info('order_order')";
$binds = $newDb->query($sql);
$binds = $binds->fetchAll(PDO::FETCH_ASSOC);
$binds = ":" . implode(",:", array_column($binds, 'name'));

$stmt = $newDb->prepare("INSERT INTO order_order VALUES ($binds)");
$newDb->beginTransaction();

foreach ($newOrderRecords as $order) {
    $stmt->execute($order);
}

$newDb->commit();

// ---------------------------------------------------------------------------

// Insert test records

foreach ($newTypeRecords as $table => $records) {
    $sql = "PRAGMA table_info('order_$table')";
    $binds = $newDb->query($sql);
    $binds = $binds->fetchAll(PDO::FETCH_ASSOC);
    $binds = ":" . implode(",:", array_column($binds, 'name'));

    $stmt = $newDb->prepare("INSERT INTO order_$table VALUES ($binds)");
    $newDb->beginTransaction();

    foreach ($records as $record) {
        $stmt->execute($record);
    }

    $newDb->commit();
}
