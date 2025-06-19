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
    
    if (!isset($data->planner_id) || !isset($data->day_of_week)) {
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
    
    // Insert or update recap
    $query = "INSERT INTO nightly_recaps (planner_id, day_of_week, notes, prompt_1_response, prompt_2_response, prompt_3_response, tasks_completed, tasks_deferred, tasks_cancelled, tasks_incomplete) 
              VALUES (:planner_id, :day_of_week, :notes, :prompt_1, :prompt_2, :prompt_3, :completed, :deferred, :cancelled, :incomplete)
              ON DUPLICATE KEY UPDATE 
              notes = VALUES(notes),
              prompt_1_response = VALUES(prompt_1_response),
              prompt_2_response = VALUES(prompt_2_response),
              prompt_3_response = VALUES(prompt_3_response),
              tasks_completed = VALUES(tasks_completed),
              tasks_deferred = VALUES(tasks_deferred),
              tasks_cancelled = VALUES(tasks_cancelled),
              tasks_incomplete = VALUES(tasks_incomplete)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':planner_id', $data->planner_id);
    $stmt->bindParam(':day_of_week', $data->day_of_week);
    $stmt->bindParam(':notes', $data->notes);
    $stmt->bindParam(':prompt_1', $data->prompt_1_response);
    $stmt->bindParam(':prompt_2', $data->prompt_2_response);
    $stmt->bindParam(':prompt_3', $data->prompt_3_response);
    $tasks_completed = isset($data->tasks_completed) ? $data->tasks_completed : 0;
    $stmt->bindParam(':completed', $tasks_completed);
    $tasks_deferred = isset($data->tasks_deferred) ? $data->tasks_deferred : 0;
    $stmt->bindParam(':deferred', $tasks_deferred);
    $tasks_cancelled = isset($data->tasks_cancelled) ? $data->tasks_cancelled : 0;
    $stmt->bindParam(':cancelled', $tasks_cancelled);
    $tasks_incomplete = isset($data->tasks_incomplete) ? $data->tasks_incomplete : 0;
    $stmt->bindParam(':incomplete', $tasks_incomplete);
    
    if ($stmt->execute()) {
        echo json_encode(array("message" => "Recap updated successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Unable to update recap"));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Server error: " . $e->getMessage()));
}