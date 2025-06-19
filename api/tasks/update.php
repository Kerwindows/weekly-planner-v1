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
    
    if (!isset($data->planner_id) || !isset($data->day_of_week) || !isset($data->task_type) || !isset($data->task_number)) {
        http_response_code(400);
        echo json_encode(array("message" => "Missing required fields"));
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
    
    // Check if task already exists
    $check_query = "SELECT id FROM daily_tasks WHERE planner_id = :planner_id AND day_of_week = :day_of_week AND task_type = :task_type AND task_number = :task_number";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':planner_id', $data->planner_id, PDO::PARAM_INT);
    $check_stmt->bindParam(':day_of_week', $data->day_of_week);
    $check_stmt->bindParam(':task_type', $data->task_type);
    $check_stmt->bindParam(':task_number', $data->task_number, PDO::PARAM_INT);
    $check_stmt->execute();
    
    $priority = isset($data->priority) ? $data->priority : 'none';
    $description = isset($data->description) ? $data->description : '';
    $status = isset($data->status) ? $data->status : 'pending';
    
    if ($check_stmt->rowCount() > 0) {
        // Update existing task with priority support
        $query = "UPDATE daily_tasks SET 
                  description = :description,
                  status = :status,
                  priority = :priority,
                  updated_at = CURRENT_TIMESTAMP
                  WHERE planner_id = :planner_id 
                  AND day_of_week = :day_of_week 
                  AND task_type = :task_type 
                  AND task_number = :task_number";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':planner_id', $data->planner_id, PDO::PARAM_INT);
        $stmt->bindParam(':day_of_week', $data->day_of_week);
        $stmt->bindParam(':task_type', $data->task_type);
        $stmt->bindParam(':task_number', $data->task_number, PDO::PARAM_INT);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':priority', $priority);
    } else {
        // Insert new task with priority support
        $query = "INSERT INTO daily_tasks (planner_id, day_of_week, task_type, task_number, description, status, priority) 
                  VALUES (:planner_id, :day_of_week, :task_type, :task_number, :description, :status, :priority)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':planner_id', $data->planner_id, PDO::PARAM_INT);
        $stmt->bindParam(':day_of_week', $data->day_of_week);
        $stmt->bindParam(':task_type', $data->task_type);
        $stmt->bindParam(':task_number', $data->task_number, PDO::PARAM_INT);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':priority', $priority);
    }
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(array(
            "message" => "Task updated successfully",
            "task" => array(
                "planner_id" => $data->planner_id,
                "day_of_week" => $data->day_of_week,
                "task_type" => $data->task_type,
                "task_number" => $data->task_number,
                "description" => $description,
                "status" => $status,
                "priority" => $priority
            )
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Unable to update task"));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Server error: " . $e->getMessage()));
}
?>