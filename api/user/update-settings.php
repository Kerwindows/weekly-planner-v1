<?php
// api/user/update-settings.php
require_once '../../config/database.php';
require_once '../../config/cors.php';
require_once '../../helpers/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth();

$token = $auth->getBearerToken();
$user_id = $auth->validateToken($token);

if (!$user_id) {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized"));
    exit();
}

$data = json_decode(file_get_contents("php://input"));

// Validate time values
$time_start = isset($data->time_start) ? intval($data->time_start) : 6;
$time_end = isset($data->time_end) ? intval($data->time_end) : 18;

// Ensure valid range (0-23)
$time_start = max(0, min(23, $time_start));
$time_end = max(0, min(23, $time_end));

// Update user settings
$query = "UPDATE users SET time_start = :time_start, time_end = :time_end WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':time_start', $time_start);
$stmt->bindParam(':time_end', $time_end);
$stmt->bindParam(':user_id', $user_id);

if ($stmt->execute()) {
    echo json_encode(array("message" => "Settings updated successfully"));
} else {
    http_response_code(500);
    echo json_encode(array("message" => "Unable to update settings"));
}