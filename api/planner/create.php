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

    if (!isset($data->week_start_date) || !isset($data->main_goal)) {
        http_response_code(400);
        echo json_encode(array("message" => "Missing required fields"));
        exit();
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data->week_start_date)) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid week_start_date format"));
        exit();
    }

    $query = "INSERT INTO weekly_planners (user_id, week_start_date, main_goal, secondary_goal_1, secondary_goal_2) 
              VALUES (:user_id, :week_start_date, :main_goal, :secondary_goal_1, :secondary_goal_2)
              ON DUPLICATE KEY UPDATE 
              main_goal = VALUES(main_goal),
              secondary_goal_1 = VALUES(secondary_goal_1),
              secondary_goal_2 = VALUES(secondary_goal_2)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':week_start_date', $data->week_start_date);
    $stmt->bindParam(':main_goal', $data->main_goal);
    $stmt->bindParam(':secondary_goal_1', $data->secondary_goal_1);
    $stmt->bindParam(':secondary_goal_2', $data->secondary_goal_2);

    if ($stmt->execute()) {
        $planner_id = $db->lastInsertId();
        if ($planner_id == 0) {
            $query = "SELECT id FROM weekly_planners WHERE user_id = :user_id AND week_start_date = :week_start_date";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':week_start_date', $data->week_start_date);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $planner_id = $row['id'];
        }

        http_response_code(200);
        echo json_encode(array("message" => "Planner created/updated successfully.", "planner_id" => $planner_id));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Unable to create/update planner."));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Server error: " . $e->getMessage()));
}
?>