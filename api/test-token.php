<?php
require_once '../config/cors.php';
require_once '../helpers/auth.php';

$auth = new Auth();

// Get the token
$authHeader = $auth->getAuthorizationHeader();
$token = $auth->getBearerToken();

echo json_encode([
    "auth_header" => $authHeader,
    "bearer_token" => $token,
    "token_length" => strlen($token),
    "validation_test" => null
]);

// Test validation
if ($token) {
    try {
        $user_id = $auth->validateToken($token);
        echo "\n\n";
        echo json_encode([
            "validation_result" => $user_id,
            "success" => $user_id !== false
        ]);
    } catch (Exception $e) {
        echo "\n\n";
        echo json_encode([
            "validation_error" => $e->getMessage(),
            "error_type" => get_class($e)
        ]);
    }
}