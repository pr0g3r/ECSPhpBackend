<?php
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

//clear debug log file
file_put_contents('debug.log', json_encode($message, JSON_PRETTY_PRINT));

// NOTE: data send is encoded as jso
$frontend_input = json_decode(file_get_contents('php://input'), true);

debugToLog([
   'uri' => $_SERVER['REQUEST_URI'],
   'method' => $_SERVER['REQUEST_METHOD'],
   'headers' => getallheaders(),
   'body' => $$frontend_input, // Log raw body for debugging
]);

// Parse the URI to extract the path
$uri = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));

debugToLog([$uri, $uri_segments]);

if(uriMatch('/api/token', $uri)){
   $response = [
      'access' => 'myaccesstoken',
      'refresh' => 'myrefreshtoken',
      'user_id' => 848484
   ];
}

// Output received data
echo json_encode($response);

function debugToLog($message) {
   file_put_contents('debug.log', json_encode($message, JSON_PRETTY_PRINT), FILE_APPEND);
}
