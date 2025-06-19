<?php
/**
 * Weekly Goals Update API Endpoint
 * Updates or creates a weekly goal for a specific planner
 */

// Set content type
header('Content-Type: application/json');

// Include configuration and dependencies
require_once '../../config.php';
require_once '../../config/database.php';
require_once '../../helpers/auth.php';

// Set CORS headers
setCorsHeaders();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed. Only POST requests are accepted."
    ]);
    exit();
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Initialize authentication
    $auth = new Auth();
    
    // Get and validate authentication token
    $token = $auth->getBearerToken();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Authentication token required"
        ]);
        exit();
    }
    
    $user_id = $auth->validateToken($token);
    
    if (!$user_id) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid or expired authentication token"
        ]);
        exit();
    }
    
    // Get and validate input data
    $input = file_get_contents("php://input");
    
    if (empty($input)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Request body is required"
        ]);
        exit();
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON format: " . json_last_error_msg()
        ]);
        exit();
    }
    
    // Validate required fields
    $required_fields = ['planner_id', 'goal_number'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Missing required fields: " . implode(', ', $missing_fields)
        ]);
        exit();
    }
    
    // Validate field types and ranges
    $planner_id = filter_var($data['planner_id'], FILTER_VALIDATE_INT);
    $goal_number = filter_var($data['goal_number'], FILTER_VALIDATE_INT);
    
    if ($planner_id === false || $planner_id <= 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid planner_id. Must be a positive integer."
        ]);
        exit();
    }
    
    if ($goal_number === false || $goal_number < 1 || $goal_number > 10) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid goal_number. Must be between 1 and 10."
        ]);
        exit();
    }
    
    // Validate optional fields
    $description = isset($data['description']) ? trim($data['description']) : '';
    $is_completed = isset($data['is_completed']) ? (bool)$data['is_completed'] : false;
    
    // Validate description length
    $max_description_length = config('validation.goal_max_length', 500);
    if (strlen($description) > $max_description_length) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Description too long. Maximum {$max_description_length} characters allowed."
        ]);
        exit();
    }
    
    // Verify planner belongs to the authenticated user
    $verification_query = "SELECT id, user_id FROM weekly_planners WHERE id = :planner_id AND user_id = :user_id";
    $verification_stmt = $db->prepare($verification_query);
    $verification_stmt->bindParam(':planner_id', $planner_id, PDO::PARAM_INT);
    $verification_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $verification_stmt->execute();
    
    $planner = $verification_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$planner) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Access denied. Planner not found or you don't have permission to modify it."
        ]);
        exit();
    }
    
    // Begin transaction for data consistency
    $db->beginTransaction();
    
    try {
        // Insert or update goal using ON DUPLICATE KEY UPDATE
        $goal_query = "INSERT INTO weekly_goals (planner_id, goal_number, description, is_completed, created_at, updated_at) 
                       VALUES (:planner_id, :goal_number, :description, :is_completed, NOW(), NOW())
                       ON DUPLICATE KEY UPDATE 
                       description = VALUES(description),
                       is_completed = VALUES(is_completed),
                       updated_at = NOW()";
        
        $goal_stmt = $db->prepare($goal_query);
        $goal_stmt->bindParam(':planner_id', $planner_id, PDO::PARAM_INT);
        $goal_stmt->bindParam(':goal_number', $goal_number, PDO::PARAM_INT);
        $goal_stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $goal_stmt->bindParam(':is_completed', $is_completed, PDO::PARAM_BOOL);
        
        if (!$goal_stmt->execute()) {
            throw new Exception("Failed to update goal in database");
        }
        
        // Get the updated/created goal
        $fetch_query = "SELECT * FROM weekly_goals WHERE planner_id = :planner_id AND goal_number = :goal_number";
        $fetch_stmt = $db->prepare($fetch_query);
        $fetch_stmt->bindParam(':planner_id', $planner_id, PDO::PARAM_INT);
        $fetch_stmt->bindParam(':goal_number', $goal_number, PDO::PARAM_INT);
        $fetch_stmt->execute();
        
        $updated_goal = $fetch_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$updated_goal) {
            throw new Exception("Failed to retrieve updated goal");
        }
        
        // Update the weekly planner's updated_at timestamp
        $update_planner_query = "UPDATE weekly_planners SET updated_at = NOW() WHERE id = :planner_id";
        $update_planner_stmt = $db->prepare($update_planner_query);
        $update_planner_stmt->bindParam(':planner_id', $planner_id, PDO::PARAM_INT);
        $update_planner_stmt->execute();
        
        // Commit transaction
        $db->commit();
        
        // Prepare response data
        $response_data = [
            "success" => true,
            "message" => "Goal updated successfully",
            "data" => [
                "goal" => [
                    "id" => (int)$updated_goal['id'],
                    "planner_id" => (int)$updated_goal['planner_id'],
                    "goal_number" => (int)$updated_goal['goal_number'],
                    "description" => $updated_goal['description'],
                    "is_completed" => (bool)$updated_goal['is_completed'],
                    "created_at" => $updated_goal['created_at'],
                    "updated_at" => $updated_goal['updated_at']
                ]
            ]
        ];
        
        // Log successful update in development mode
        if (config('app.debug')) {
            error_log("Goal updated successfully - User ID: $user_id, Planner ID: $planner_id, Goal Number: $goal_number");
        }
        
        http_response_code(200);
        echo json_encode($response_data);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    // Database-specific errors
    error_log("Database error in goals/update.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => config('app.debug') ? "Database error: " . $e->getMessage() : "A database error occurred"
    ]);
    
} catch (Exception $e) {
    // General application errors
    error_log("Error in goals/update.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => config('app.debug') ? "Server error: " . $e->getMessage() : "An internal server error occurred"
    ]);
    
} finally {
    // Clean up database connection
    if (isset($database)) {
        $database->closeConnection();
    }
}
?>