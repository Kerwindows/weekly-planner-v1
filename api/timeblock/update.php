<?php
require_once '../../config/database.php';
require_once '../../config/cors.php';
require_once '../../helpers/auth.php';

try {
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
    
    if (!isset($data->planner_id) || !isset($data->day_of_week) || !isset($data->hour)) {
        http_response_code(400);
        echo json_encode(array("message" => "Missing required fields"));
        exit();
    }
    
    // Verify planner belongs to user
    $query = "SELECT id FROM weekly_planners WHERE id = :planner_id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':planner_id', $data->planner_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        http_response_code(403);
        echo json_encode(array("message" => "Access denied"));
        exit();
    }
    
    // Delete block if description is empty
    if (empty($data->description)) {
        $query = "DELETE FROM time_blocks WHERE planner_id = :planner_id AND day_of_week = :day_of_week AND hour = :hour";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':planner_id', $data->planner_id);
        $stmt->bindParam(':day_of_week', $data->day_of_week);
        $stmt->bindParam(':hour', $data->hour);
        $stmt->execute();
        echo json_encode(array("message" => "Time block deleted"));
        exit();
    }
    
    // Insert or update time block
    $query = "INSERT INTO time_blocks (planner_id, day_of_week, hour, description) 
              VALUES (:planner_id, :day_of_week, :hour, :description)
              ON DUPLICATE KEY UPDATE 
              description = VALUES(description)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':planner_id', $data->planner_id);
    $stmt->bindParam(':day_of_week', $data->day_of_week);
    $stmt->bindParam(':hour', $data->hour);
    $stmt->bindParam(':description', $data->description);
    
    if ($stmt->execute()) {
        echo json_encode(array("message" => "Time block updated successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Unable to update time block"));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Server error: " . $e->getMessage()));
}