<?php
/**
 * User Registration API Endpoint
 * Creates new user account with validation and security checks
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

// Enable error reporting in development mode only
if (config('app.debug')) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Initialize authentication helper
    $auth = new Auth();
    
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
    $required_fields = ['username', 'email', 'password'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($data[$field]) || trim($data[$field]) === '') {
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
    
    // Sanitize and validate input
    $username = trim($data['username']);
    $email = trim(strtolower($data['email']));
    $password = $data['password'];
    
    // Username validation
    if (strlen($username) < 3 || strlen($username) > 50) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Username must be between 3 and 50 characters"
        ]);
        exit();
    }
    
    if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Username can only contain letters, numbers, dots, hyphens, and underscores"
        ]);
        exit();
    }
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid email address format"
        ]);
        exit();
    }
    
    if (strlen($email) > 100) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email address is too long (maximum 100 characters)"
        ]);
        exit();
    }
    
    // Password validation
    $min_password_length = config('validation.password_min_length', 6);
    if (strlen($password) < $min_password_length) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Password must be at least {$min_password_length} characters long"
        ]);
        exit();
    }
    
    // Optional: Strong password requirements
    if (config('auth.require_strong_password', false)) {
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $password)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character"
            ]);
            exit();
        }
    }
    
    // Rate limiting check
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!$auth->checkRateLimit("register_$client_ip", config('rate_limit.auth', 5), 3600)) {
        http_response_code(429);
        echo json_encode([
            "success" => false,
            "message" => "Too many registration attempts. Please try again later."
        ]);
        exit();
    }
    
    // Check if username or email already exists
    $check_query = "SELECT id, username, email FROM users WHERE username = :username OR email = :email";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $check_stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $existing_user = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_user['username'] === $username) {
            http_response_code(409);
            echo json_encode([
                "success" => false,
                "message" => "Username is already taken"
            ]);
            exit();
        }
        
        if ($existing_user['email'] === $email) {
            http_response_code(409);
            echo json_encode([
                "success" => false,
                "message" => "Email address is already registered"
            ]);
            exit();
        }
    }
    
    // Hash the password
    $password_hash = $auth->hashPassword($password);
    
    // Get optional fields with defaults
    $time_start = isset($data['time_start']) ? (int)$data['time_start'] : config('datetime.default_work_start', 6);
    $time_end = isset($data['time_end']) ? (int)$data['time_end'] : config('datetime.default_work_end', 18);
    
    // Validate time values
    if ($time_start < 0 || $time_start > 23) {
        $time_start = 6;
    }
    if ($time_end < 0 || $time_end > 23 || $time_end <= $time_start) {
        $time_end = 18;
    }
    
    // Begin transaction for atomicity
    $db->beginTransaction();
    
    try {
        // Insert the new user
        $insert_query = "INSERT INTO users (username, email, password_hash, time_start, time_end, created_at, updated_at) 
                         VALUES (:username, :email, :password_hash, :time_start, :time_end, NOW(), NOW())";
        
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $insert_stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $insert_stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
        $insert_stmt->bindParam(':time_start', $time_start, PDO::PARAM_INT);
        $insert_stmt->bindParam(':time_end', $time_end, PDO::PARAM_INT);
        
        if (!$insert_stmt->execute()) {
            throw new Exception("Failed to insert user record");
        }
        
        $user_id = $db->lastInsertId();
        
        // Commit transaction
        $db->commit();
        
        // Prepare response data (exclude sensitive information)
        $user_data = [
            "id" => (int)$user_id,
            "username" => $username,
            "email" => $email,
            "time_start" => $time_start,
            "time_end" => $time_end
        ];
        
        // Log successful registration
        if (config('app.debug')) {
            error_log("User registered successfully - ID: $user_id, Username: $username, Email: $email");
        }
        
        // Return successful response
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "User registered successfully",
            "data" => [
                "user" => $user_data
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    // Database-specific errors
    error_log("Database error in auth/register.php: " . $e->getMessage());
    
    // Check for specific constraint violations
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        if (strpos($e->getMessage(), 'username') !== false) {
            $error_message = "Username is already taken";
        } elseif (strpos($e->getMessage(), 'email') !== false) {
            $error_message = "Email address is already registered";
        } else {
            $error_message = "This username or email is already registered";
        }
        
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => $error_message
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => config('app.debug') ? "Database error: " . $e->getMessage() : "A database error occurred"
        ]);
    }
    
} catch (Exception $e) {
    // General application errors
    error_log("Error in auth/register.php: " . $e->getMessage());
    
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