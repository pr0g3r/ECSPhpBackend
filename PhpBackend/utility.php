<?php
/* 
From firefox debugger, this is what the django sends after logging in
token: {…}
​​
access: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbl90eXBlIjoiYWNjZXNzIiwiZXhwIjoxNzIzNzE0NTA0LCJpYXQiOjE3MjM3MDczMDQsImp0aSI6ImU2NWExMDE2MjJlMzQ3YTNiZDZiZDJiMjA1ODFhMDFhIiwidXNlcl9pZCI6NH0.XLh9YflW7n8PN1NiWIPMPPLkWHCbfePixP-rDvK1KD0"
​​
refresh: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbl90eXBlIjoicmVmcmVzaCIsImV4cCI6MTcyMzc5MzcwNCwiaWF0IjoxNzIzNzA3MzA0LCJqdGkiOiI4YjUwNjk5OWIzYTk0NDUyYWFmZTAwNjkwNTVhMzI3OCIsInVzZXJfaWQiOjR9.yKTpAKzTvLSjqbs40qhy2aajiCOrFR_oThwquh8Pdok"
​​
<prototype>: {…}
​
user: {…}
​​
exp: 1723714504
​​
iat: 1723707304
​​
jti: "e65a101622e347a3bd6bd2b20581a01a"
​​
token_type: "access"
​​
user_id: 4
*/
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function encodeJwt($payload) {
    // Header
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);

    // Encode Header
    $base64UrlHeader = base64UrlEncode($header);

    // Encode Payload
    $base64UrlPayload = base64UrlEncode(json_encode($payload));

    // Concatenate header and payload to form the JWT
    $unsignedToken = $base64UrlHeader . '.' . $base64UrlPayload;

    // Normally, you'd sign the token here and append the signature
    // For simplicity, we skip the signature and add an empty string at the end
    $jwt = $unsignedToken . '.';

    return $jwt;
}

function uriMatch($uri, $uri_segments){
    $uri = explode('/', $uri);

    debugToLog([$uri, $uri_segments]);
    foreach ($uri as $i => $segment) {
        if($i === 0){ continue; }
        if($segment !== $uri_segments[$i]){
            return false;
        }
    }
    return true;
}
