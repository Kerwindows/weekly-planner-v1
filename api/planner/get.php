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
    
    // Updated to get Sunday of current week instead of Monday
    $week_start_date = isset($_GET['week_start_date']) ? $_GET['week_start_date'] : date('Y-m-d', strtotime('sunday this week'));
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $week_start_date)) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid week_start_date format"));
        exit();
    }
    
    $query = "SELECT * FROM weekly_planners WHERE user_id = :user_id AND week_start_date = :week_start_date";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':week_start_date', $week_start_date);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $planner = $stmt->fetch(PDO::FETCH_ASSOC);
        $planner_id = $planner['id'];
        
        // Load weekly goals
        $query = "SELECT * FROM weekly_goals WHERE planner_id = :planner_id ORDER BY goal_number";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':planner_id', $planner_id, PDO::PARAM_INT);
        $stmt->execute();
        $planner['weekly_goals'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load daily tasks
        $query = "SELECT * FROM daily_tasks WHERE planner_id = :planner_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':planner_id', $planner_id, PDO::PARAM_INT);
        $stmt->execute();
        $planner['daily_tasks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load time blocks
        $query = "SELECT * FROM time_blocks WHERE planner_id = :planner_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':planner_id', $planner_id, PDO::PARAM_INT);
        $stmt->execute();
        $planner['time_blocks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load nightly recaps
        $query = "SELECT * FROM nightly_recaps WHERE planner_id = :planner_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':planner_id', $planner_id, PDO::PARAM_INT);
        $stmt->execute();
        $planner['nightly_recaps'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($planner);
    } else {
        echo json_encode(array("message" => "No planner found for this week."));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Server error: " . $e->getMessage()));
}
?>