<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

echo json_encode([
    "headers" => getallheaders(),
    "server_auth_vars" => array_filter($_SERVER, function($key) {
        return stripos($key, 'auth') !== false || stripos($key, 'http') !== false;
    }, ARRAY_FILTER_USE_KEY),
    "authorization_found" => isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : 'NOT FOUND'
]);