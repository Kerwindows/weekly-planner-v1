<?php
require_once '../../config/database.php';
require_once '../../config/cors.php';
require_once '../../helpers/auth.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth();
    $token = $auth->getBearerToken();
    $user_id = $auth->validateToken($token);
    
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized: Invalid or missing token"));
        exit();
    }
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->planner_id)) {
        http_response_code(400);
        echo json_encode(array("message" => "Missing planner_id"));
        exit();
    }
    
    // Verify that the planner belongs to this user
    $verify_query = "SELECT id FROM weekly_planners WHERE id = :planner_id AND user_id = :user_id";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->bindParam(':planner_id', $data->planner_id, PDO::PARAM_INT);
    $verify_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $verify_stmt->execute();
    
    if ($verify_stmt->rowCount() == 0) {
        http_response_code(403);
        echo json_encode(array("message" => "Forbidden: Planner not found or not owned by user"));
        exit();
    }
    
    $query = "UPDATE weekly_planners SET 
              main_goal = :main_goal,
              secondary_goal_1 = :secondary_goal_1,
              secondary_goal_2 = :secondary_goal_2,
              updated_at = CURRENT_TIMESTAMP
              WHERE id = :planner_id AND user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':planner_id', $data->planner_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':main_goal', $data->main_goal);
    $stmt->bindParam(':secondary_goal_1', $data->secondary_goal_1);
    $stmt->bindParam(':secondary_goal_2', $data->secondary_goal_2);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(array("message" => "Planner updated successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Unable to update planner"));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Server error: " . $e->getMessage()));
}
?>