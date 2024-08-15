<?php
/*
http://localhost/ELIXIR/ecs_php_db_query/index.php

cd /var/www/html/ELIXIR/ecs_php_db_query/
php -S 127.0.0.1:8000

http://127.0.0.1:8000/resends
http://127.0.0.1:8000/resends/?page=3
http://127.0.0.1:8000/resends/get_order_count/2023-04-05/2023-04-12
http://127.0.0.1:8000/resends/get_reason_count/2023-04-05/2023-04-12
http://127.0.0.1:8000/resends/get_reason_daily/2023-04-05/2023-04-12
http://127.0.0.1:8000/resends/get_warehouse_count/picker/2023-04-05/2023-04-12
http://127.0.0.1:8000/resends/get_warehouse_count/packer/2023-04-05/2023-04-12
http://127.0.0.1:8000/resends/get_room_count/2023-04-05/2023-04-12
http://127.0.0.1:8000/returns/get_order_count/2023-04-05/2023-04-12
http://127.0.0.1:8000/returns/get_reason_count/2023-04-05/2023-04-12
http://127.0.0.1:8000/returns/get_reason_daily/2023-04-05/2023-04-12
http://127.0.0.1:8000/returns/action_customer_count/2023-04-05/2023-04-12
http://127.0.0.1:8000/returns/action_product_count/2023-04-05/2023-04-12
http://127.0.0.1:8000/refunds/get_order_count/2023-04-05/2023-04-12
http://127.0.0.1:8000/refunds/get_reason_count/2023-04-05/2023-04-12
http://127.0.0.1:8000/refunds/get_reason_daily/2023-04-05/2023-04-12
http://127.0.0.1:8000/refunds/get_amount_daily/2023-04-05/2023-04-12
http://127.0.0.1:8000/refunds/get_void_daily/2023-04-05/2023-04-12

http://127.0.0.1:8000/refunds/?page=3
*/

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
// set_time_limit(30);
// ini_set("memory_limit", "-1");

function dd($data)
{
   echo '<pre style="background:#111; color:#b5ce28; font-size:11px;">'; print_r($data); echo '</pre>'; die();
}


/*
Setup mod write:
#######################################################################

# Enable module rewrite:
$ sudo a2enmod rewrite

Set AllowOverride to 'All' in '/etc/apache2/apache2.conf':

------------------------------
<Directory /var/www/>
	Options Indexes FollowSymLinks
	AllowOverride All
	Require all granted
</Directory>
------------------------------

# Restart Apache:
$ sudo systemctl restart apache2

# Check that 'mod_rewrite' is enabled via phpinfo():
Search for the 'Loaded Modules' section, and look for 'mod_rewrite'.

#######################################################################

dd(parse_url($_SERVER['REQUEST_URI']));
dd($_SERVER);
*/

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");


$path_info = $_SERVER['PATH_INFO'] ?? '';

$page = '';
if (isset($_SERVER['QUERY_STRING']) && 'page=' == substr($_SERVER['QUERY_STRING'], 0,5)) {
	$page = substr($_SERVER['QUERY_STRING'], -1);
}

// Remove leading '/' then split on '/'.
// $type is the 1st segment.
$path_info = explode('/', substr($path_info, 1));

$type = $path_info[0];

$arr_size = (int)count($path_info);

if ($arr_size > 1 && '' != $path_info[1]) {
	$func = $path_info[1];
	$op = 5 == $arr_size ? $path_info[2] : '';
	$date_start = $path_info[$arr_size-2];
	$date_end = $path_info[$arr_size-1];
}

$db = new PDO('sqlite:db.sqlite3');

$tbl = 'order_'. substr($type, 0,-1);

if (isset($func)) {
	if ('get_order_count' == $func) {
	   	$sql = "SELECT COUNT(*) AS order_total, created
				FROM $tbl
				WHERE created BETWEEN '$date_start' AND '$date_end'
				GROUP BY created";
	}
	elseif ('get_reason_count' == $func) {
		$sql = "SELECT COUNT(*) AS order_total, o.name AS reason
				FROM $tbl r
				JOIN order_reason o ON r.reason_id = o.id
				WHERE r.created BETWEEN '$date_start' AND '$date_end'
				GROUP BY o.name";
	}
	elseif ('get_reason_daily' == $func) {
		$sql = "SELECT COUNT(*) AS order_total, r.created, o.name AS reason
				FROM $tbl r
				JOIN order_reason o ON r.reason_id = o.id
				WHERE r.created BETWEEN '$date_start' AND '$date_end'
				GROUP BY r.created, r.reason_id";
	}
	elseif ('get_warehouse_count' == $func) {
		$sql = "SELECT COUNT(*) AS order_total, o.name AS warehouse
				FROM $tbl r
				JOIN order_warehouse o ON r.{$op}_id = o.id
				WHERE r.created BETWEEN '$date_start' AND '$date_end'
				GROUP BY o.name";
	}
	elseif ('get_room_count' == $func) {
		$sql = "SELECT COUNT(*) AS order_total, o.name AS room
				FROM $tbl r
				JOIN order_room o ON r.room_id = o.id
				WHERE r.created BETWEEN '$date_start' AND '$date_end'
				GROUP BY o.name";
	}
	elseif ('action_customer_count' == $func) {
		$sql = "SELECT COUNT(*) AS order_total, o.name AS action
				FROM $tbl r
				JOIN order_action_customer o ON r.action_customer_id = o.id
				WHERE r.created BETWEEN '$date_start' AND '$date_end'
				GROUP BY o.name";
	}
	elseif ('action_product_count' == $func) {
		$sql = "SELECT COUNT(o_ri.action_product_id) AS order_total, o_ap.name
				FROM order_return o_r
				INNER JOIN order_returnitems o_ri ON o_r.order_id = o_ri.order_id
				INNER JOIN order_action_product o_ap ON o_ri.action_product_id = o_ap.id
				WHERE o_r.created BETWEEN '$date_start' AND '$date_end'
				GROUP BY o_ri.action_product_id";
	}
	elseif ('get_amount_daily' == $func) {
		$sql = "SELECT ROUND(SUM(amount), 2) AS amount_total, created
				FROM $tbl
				WHERE created BETWEEN '$date_start' AND '$date_end'
				GROUP BY created";
	}
	elseif ('get_void_daily' == $func) {
		$sql = "SELECT COUNT(void_order) AS order_total, created
				FROM $tbl
				WHERE created BETWEEN '$date_start' AND '$date_end'
				AND void_order = 0
				GROUP BY created";
	}
	
	$res = $db->query($sql);
	$output = $res->fetchAll(PDO::FETCH_ASSOC);

	print_json($output);
}
elseif ($type) {
	$items_per_page = 30;
	$offset = 0;
	$order_by = '';
	
	if ($page) {
		$offset = (int)$items_per_page * ($page - 1);
	}
	
	$sql = "SELECT * FROM `$tbl` $order_by LIMIT $items_per_page OFFSET $offset";
	$res = $db->query($sql);
	$output = $res->fetchAll(PDO::FETCH_ASSOC);
	
	print_json($output);
}

function print_json($output)
{
   $json_results = json_encode($output, JSON_PRETTY_PRINT);
   header ('Content-Type: application/json');
   print_r($json_results);
}