<?php
/*
http://192.168.0.125/ecs/hide_part_tracking.php
*/

$db = new PDO('sqlite:db_copy.sqlite3');

// UPDATE `order_resend` SET `option_id` = 50 WHERE `option_id` = 2 AND `created` > '2022-03-31'
$stmt = $db->prepare("UPDATE `order_resend` SET `option_id` = ? WHERE `option_id` = ? AND `rowid` > ?");
// $db->beginTransaction();
// $stmt->execute([ '50', '2', '1' ]);
// $db->commit();

$stmt = $db->prepare("UPDATE `order_resend` SET `option_id` = ? WHERE `option_id` = ?");
// $db->beginTransaction();
// $stmt->execute([ '2', '50' ]);
// $db->commit();

// $sql = "SELECT rowid,* FROM `order_resend` WHERE `option_id` = 2";
$sql = "SELECT order_id FROM `order_resend` WHERE `option_id` = 2";
$results = $db->query($sql);
// $results = $results->fetchAll(PDO::FETCH_ASSOC); // ::FETCH_NUM ::FETCH_COLUMN ::FETCH_KEY_PAIR
$results = $results->fetchAll(PDO::FETCH_COLUMN); // ::FETCH_NUM ::FETCH_COLUMN ::FETCH_KEY_PAIR

echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r($results); echo '</pre>'; die();


$sql = "SELECT rowid,* FROM `order_resend` WHERE `option_id` = 2 AND `rowid` < 213";
$results = $db->query($sql);
$results = $results->fetchAll(PDO::FETCH_ASSOC);

echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r($results); echo '</pre>';