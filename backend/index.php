<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set("error_log", "errors.log");
error_reporting(E_ALL);

// Json Header
header('Content-Type: application/json');

// Allow cross origin from anywhere
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: X-Requested-With, Origin, Content-Type, Cookie, Accept, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // End the script execution for OPTIONS request
    exit;
}

require_once 'utility.php';

try{
   $db = new PDO('sqlite:ecs.db3');
   $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
   debugToLog("Database connection failed: " . $e->getMessage());
   die();
}

//clear debug log file
//file_put_contents('debug.log', json_encode($message, JSON_PRETTY_PRINT));

// NOTE: data send is encoded as jso
$frontend_input = json_decode(file_get_contents('php://input'), true);

debugToLog([
   'uri' => $_SERVER['REQUEST_URI'],
   'method' => $_SERVER['REQUEST_METHOD'],
]);

// Parse the URI to extract the path, array_slice to remove the first element which is always 'index.php'
$uri = array_slice(explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/')), 1);

// Parse query parameters, is they exist explode into an array, otherwise set null
$query_params_raw = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$query_params_raw = (!empty($query_params_raw) && false !== strpos($query_params_raw,'/')) ? substr($query_params_raw, 1) : $query_params_raw;
$query_params_array = (!empty($query_params_raw)) ? explode('&', $query_params_raw) : $query_params_raw;
// Split each parameter into its own key value pair
if(!is_null($query_params_array)){
   $query_params = [];
   foreach ($query_params_array as $param) {
      $key_value = explode('=', $param);
      if (isset($key_value[0]) && isset($key_value[1])) {
         $query_params[$key_value[0]] = $key_value[1];
   } elseif (isset($key_value[0])) {
         // If there is a key without a value, set the value to an empty string
         $query_params[$key_value[0]] = '';
   }
   debugToLog(["Query Parameters" => $query_params]);
   }
}


//LOGIN
if(uriMatch('api/token', $uri)){
   $response = [
      'access' => 'myaccesstoken',
      'refresh' => 'myrefreshtoken',
      'user_id' => 848484
   ];
   echo json_encode($response);
   die();
}

//Returns Resends Refunds
$type_options = ['resends','returns','refunds'];
$type = $uri[0];


if ($query_params_raw === null && in_array($type, $type_options)) {
   debugToLog(["In array", $type, $uri]);
   switch($type){
      case 'resends':
         $tbl = 'order_resend';
         $func = $uri[1];
         if('get_warehouse_count' === $func){
            $pickerOrPacker = $uri[2];
            $date_start = $uri[3];
            $date_end = $uri[4];
         }
         else{
            $date_start = $uri[2];
            $date_end = $uri[3];
         }
         break;
      
      case 'returns':
         $tbl = 'order_return';
         $func = $uri[1];
         $date_start = $uri[2];
         $date_end = $uri[3];
         break;

      case 'refunds':
         $tbl = 'order_refund';
         $func = $uri[1];
         $date_start = $uri[2];
         $date_end = $uri[3];
         break;
   }

   debugToLog([
      "Type: "=> $type,
      "date start"=> $date_start,
      "date end"=> $date_end,
      "func"=> $func,
      "tbl"=> $tbl,
   ]);

   switch ($func) {
      case 'get_order_count':
         $sql = "SELECT COUNT(*) AS order_total, created
                 FROM $tbl
                 WHERE created BETWEEN '$date_start' AND '$date_end'
                 GROUP BY created";
         break;
      case 'get_reason_count':
         $sql = "SELECT COUNT(*) AS order_total, o.name AS reason
                 FROM $tbl r
                 JOIN order_reason o ON r.reason_id = o.id
                 WHERE r.created BETWEEN '$date_start' AND '$date_end'
                 GROUP BY o.name";
         break;
      case 'get_reason_daily':
         $sql = "SELECT COUNT(*) AS order_total, r.created, o.name AS reason
                 FROM $tbl r
                 JOIN order_reason o ON r.reason_id = o.id
                 WHERE r.created BETWEEN '$date_start' AND '$date_end'
                 GROUP BY r.created, r.reason_id";
         break;
      case 'get_warehouse_count':
         $sql = "SELECT COUNT(*) AS order_total, o.name AS warehouse
                 FROM $tbl r
                 JOIN order_warehouse o ON r.{$pickerOrPacker}_id = o.id
                 WHERE r.created BETWEEN '$date_start' AND '$date_end'
                 GROUP BY o.name";
         break;
      case 'get_room_count':
         $sql = "SELECT COUNT(*) AS order_total, o.name AS room
                 FROM $tbl r
                 JOIN order_room o ON r.room_id = o.id
                 WHERE r.created BETWEEN '$date_start' AND '$date_end'
                 GROUP BY o.name";
         break;
      case 'action_customer_count':
         $sql = "SELECT COUNT(*) AS order_total, o.name AS action
                 FROM $tbl r
                 JOIN order_action_customer o ON r.action_customer_id = o.id
                 WHERE r.created BETWEEN '$date_start' AND '$date_end'
                 GROUP BY o.name";
         break;
      case 'action_product_count':
         $sql = "SELECT COUNT(o_ri.action_product_id) AS order_total, o_ap.name
                 FROM order_return o_r
                 INNER JOIN order_returnitems o_ri ON o_r.order_id = o_ri.order_id
                 INNER JOIN order_action_product o_ap ON o_ri.action_product_id = o_ap.id
                 WHERE o_r.created BETWEEN '$date_start' AND '$date_end'
                 GROUP BY o_ri.action_product_id";
         break;
      case 'get_amount_daily':
         $sql = "SELECT ROUND(SUM(amount), 2) AS amount_total, created
                 FROM $tbl
                 WHERE created BETWEEN '$date_start' AND '$date_end'
                 GROUP BY created";
         break;
      case 'get_void_daily':
         $sql = "SELECT COUNT(void_order) AS order_total, created
                 FROM $tbl
                 WHERE created BETWEEN '$date_start' AND '$date_end'
                 AND void_order = 0
                 GROUP BY created";
         break;

      default:
         echo var_dump($_SERVER);
   }
   
   try{
      debugToLog($sql);
      $res = $db->query($sql);
      $output = $res->fetchAll(PDO::FETCH_ASSOC);
      debugToLog($output);
      echo json_encode($output);
      die();
   }
   catch(PDOException $e) {
      debugToLog($e->getMessage());
   }
}
else if (in_array($type, $type_options)){
   $items_per_page = 30;
   $offset = 0;
   $order_by = '';
   
   $offset = (isset($query_params['page'])) ? $offset = (int)$items_per_page * ($query_params['page'] - 1) : 0;

   switch($type){
      case 'resends':
         $tbl = 'order_resend';
         break;
      
      case 'returns':
         $tbl = 'order_return';
         break;

      case 'refunds':
         $tbl = 'order_refund';
         break;
   }

   $sql = "SELECT * FROM `$tbl` $order_by LIMIT $items_per_page OFFSET $offset";
   $res = $db->query($sql);
   $output = $res->fetchAll(PDO::FETCH_ASSOC);
   
   echo json_encode(['results' => $output, 'total_pages' => 10]);
}

else {
   debugToLog(["\$type was invalid, got $type but expected", $type_options]);
   echo json_encode(["error"=> "No corresponding function"]);
}

function debugToLog($message) {
   file_put_contents('debug.log', json_encode($message, JSON_PRETTY_PRINT), FILE_APPEND);
}
