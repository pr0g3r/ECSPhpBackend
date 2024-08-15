<?php
/*
http://localhost/ecs_backend/query_db.php?type=resends&page=3
http://localhost/ecs_backend/query_db.php?type=resends&ordering=-created
http://localhost/ecs_backend/query_db.php?type=resends&func=get_order_count&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=resends&func=get_reason_count&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=resends&func=get_reason_daily&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=resends&func=get_warehouse_count&op=picker&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=resends&func=get_room_count&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=returns&func=get_order_count&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=returns&func=get_reason_count&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=returns&func=get_reason_daily&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=returns&func=action_customer_count&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=returns&func=action_product_count&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=refunds&func=get_order_count&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=refunds&func=get_reason_count&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=refunds&func=get_reason_daily&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=refunds&func=get_amount_daily&date_start=2023-04-05&date_end=2023-04-12
http://localhost/ecs_backend/query_db.php?type=refunds&func=get_void_daily&date_start=2023-04-05&date_end=2023-04-12


The following accesses the ECS Django database directly via the REST API:
http://192.168.0.125:8080/resends/?page=3
http://192.168.0.125:8080/resends/get_order_count/2023-04-05/2023-04-12
http://192.168.0.125:8080/resends/get_reason_count/2023-04-05/2023-04-12
http://192.168.0.125:8080/resends/get_reason_daily/2023-04-05/2023-04-12
http://192.168.0.125:8080/resends/get_warehouse_count/picker/2023-04-05/2023-04-12
http://192.168.0.125:8080/resends/get_warehouse_count/packer/2023-04-05/2023-04-12
http://192.168.0.125:8080/resends/get_room_count/2023-04-05/2023-04-12
http://192.168.0.125:8080/returns/get_order_count/2023-04-05/2023-04-12
http://192.168.0.125:8080/returns/get_reason_count/2023-04-05/2023-04-12
http://192.168.0.125:8080/returns/get_reason_daily/2023-04-05/2023-04-12
http://192.168.0.125:8080/returns/action_customer_count/2023-04-05/2023-04-12
http://192.168.0.125:8080/returns/action_product_count/2023-04-05/2023-04-12
http://192.168.0.125:8080/refunds/get_order_count/2023-04-05/2023-04-12
http://192.168.0.125:8080/refunds/get_reason_count/2023-04-05/2023-04-12
http://192.168.0.125:8080/refunds/get_reason_daily/2023-04-05/2023-04-12
http://192.168.0.125:8080/refunds/get_amount_daily/2023-04-05/2023-04-12
http://192.168.0.125:8080/refunds/get_void_daily/2023-04-05/2023-04-12
*/

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

$db_sqlite = new PDO('sqlite:ecs.db3');

$tbl = 'order_'. substr($_GET['type'], 0,-1);

if (isset($_GET['func'])) {
   $date_start = $_GET['date_start'];
   $date_end = $_GET['date_end'];

   if ('get_order_count' == $_GET['func']) {
      $sql = "SELECT COUNT(*) AS order_total, created
              FROM $tbl
              WHERE created BETWEEN '$date_start' AND '$date_end'
              GROUP BY created";
   }
   elseif ('get_reason_count' == $_GET['func']) {
      $sql = "SELECT COUNT(*) AS order_total, o.name AS reason
              FROM $tbl r
              JOIN order_reason o ON r.reason_id = o.id
              WHERE r.created BETWEEN '$date_start' AND '$date_end'
              GROUP BY o.name";
   }
   elseif ('get_reason_daily' == $_GET['func']) {
      $sql = "SELECT COUNT(*) AS order_total, r.created, o.name AS reason
              FROM $tbl r
              JOIN order_reason o ON r.reason_id = o.id
              WHERE r.created BETWEEN '$date_start' AND '$date_end'
              GROUP BY r.created, r.reason_id";
   }
   elseif ('get_warehouse_count' == $_GET['func']) {
      $sql = "SELECT COUNT(*) AS order_total, o.name AS warehouse
              FROM $tbl r
              JOIN order_warehouse o ON r.{$_GET['op']}_id = o.id
              WHERE r.created BETWEEN '$date_start' AND '$date_end'
              GROUP BY o.name";
   }
   elseif ('get_room_count' == $_GET['func']) {
      $sql = "SELECT COUNT(*) AS order_total, o.name AS room
              FROM $tbl r
              JOIN order_room o ON r.room_id = o.id
              WHERE r.created BETWEEN '$date_start' AND '$date_end'
              GROUP BY o.name";
   }
   elseif ('action_customer_count' == $_GET['func']) {
      $sql = "SELECT COUNT(*) AS order_total, o.name AS action
              FROM $tbl r
              JOIN order_action_customer o ON r.action_customer_id = o.id
              WHERE r.created BETWEEN '$date_start' AND '$date_end'
              GROUP BY o.name";
   }
   elseif ('action_product_count' == $_GET['func']) {
      $sql = "SELECT COUNT(o_ri.action_product_id) AS order_total, o_ap.name
              FROM order_return o_r
              INNER JOIN order_returnitems o_ri ON o_r.order_id = o_ri.order_id
              INNER JOIN order_action_product o_ap ON o_ri.action_product_id = o_ap.id
              WHERE o_r.created BETWEEN '$date_start' AND '$date_end'
              GROUP BY o_ri.action_product_id";
   }
   elseif ('get_amount_daily' == $_GET['func']) {
      // Round total to 2 decimal places
      $sql = "SELECT ROUND(SUM(amount), 2) AS amount_total, created
              FROM $tbl
              WHERE created BETWEEN '$date_start' AND '$date_end'
              GROUP BY created";
   }
   elseif ('get_void_daily' == $_GET['func']) {
      $sql = "SELECT COUNT(void_order) AS order_total, created
              FROM $tbl
              WHERE created BETWEEN '$date_start' AND '$date_end'
              AND void_order = 0
              GROUP BY created";
   }
   
   $results = $db_sqlite->query($sql);
   $results = $results->fetchAll(PDO::FETCH_ASSOC);
    
   print_json($results);
}

elseif (isset($_GET['type'])) {
   $items_per_page = 30;
   $offset = 0;
   $order_by = '';
   
   if (isset($_GET['page'])) {
      $offset = $items_per_page * ($_GET['page'] - 1);
   }
   if (isset($_GET['ordering'])) {
      if ('-' == substr($_GET['ordering'], 0,1)) {
         $desc = ' DESC';
         $_GET['ordering'] = substr($_GET['ordering'], 1);
      }
      
      $column = $_GET['ordering'];
      
      $order_by = "ORDER BY `$column`$desc";
   }
   
   $sql = "SELECT * FROM `$tbl` $order_by LIMIT $items_per_page OFFSET $offset";
   $results = $db_sqlite->query($sql);
   $results = $results->fetchAll(PDO::FETCH_ASSOC);
   
   print_json($results);;
}


function print_json($results)
{
   $json_results = json_encode($results, JSON_PRETTY_PRINT);
   header ('Content-Type: application/json');
   print_r($json_results);
}