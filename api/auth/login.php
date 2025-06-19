<?php
/**
 * Fixed User Login API Endpoint
 * Fixes the SQL parameter binding issue
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
    
    // Initialize authentication
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
    if (empty($data['username']) || empty($data['password'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Username and password are required"
        ]);
        exit();
    }
    
    $username = trim($data['username']);
    $password = $data['password'];
    
    // First, let's check what columns actually exist in your users table
    try {
        $check_columns_query = "DESCRIBE users";
        $check_stmt = $db->query($check_columns_query);
        $columns = $check_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (config('app.debug')) {
            error_log("Available columns in users table: " . implode(', ', $columns));
        }
        
    } catch (Exception $e) {
        error_log("Could not check table structure: " . $e->getMessage());
    }
    
    // Build the query based on what columns exist
    // Start with basic required columns
    $select_columns = ["id", "username", "password_hash"];
    
    // Add optional columns if they exist
    $optional_columns = ["email", "time_start", "time_end", "created_at", "updated_at"];
    
    foreach ($optional_columns as $col) {
        if (in_array($col, $columns)) {
            $select_columns[] = $col;
        }
    }
    
    // Build the SELECT query
    $query = "SELECT " . implode(', ', $select_columns) . " 
              FROM users 
              WHERE username = :username";
    
    // If email column exists, allow login with email too
    if (in_array('email', $columns)) {
        $query = "SELECT " . implode(', ', $select_columns) . " 
                  FROM users 
                  WHERE username = :username OR email = :email";
    }
    
    if (config('app.debug')) {
        error_log("Login query: " . $query);
        error_log("Username parameter: " . $username);
    }
    
    $stmt = $db->prepare($query);
    
    // Bind parameters based on query
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    if (in_array('email', $columns)) {
        $stmt->bindParam(':email', $username, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (config('app.debug')) {
            error_log("User found: " . $user['username']);
            error_log("Password hash from DB: " . substr($user['password_hash'], 0, 20) . "...");
        }
        
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Password is correct, generate token
            $token = $auth->createToken($user['id'], $user['username']);
            
            // Prepare user data (only include existing columns)
            $user_data = [
                "id" => (int)$user['id'],
                "username" => $user['username']
            ];
            
            // Add optional fields if they exist
            if (isset($user['email'])) {
                $user_data['email'] = $user['email'];
            }
            if (isset($user['time_start'])) {
                $user_data['time_start'] = (int)$user['time_start'];
            }
            if (isset($user['time_end'])) {
                $user_data['time_end'] = (int)$user['time_end'];
            }
            if (isset($user['created_at'])) {
                $user_data['created_at'] = $user['created_at'];
            }
            
            // Log successful login
            if (config('app.debug')) {
                error_log("Successful login - User ID: {$user['id']}, Username: {$user['username']}");
            }
            
            // Return successful response
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Login successful",
                "token" => $token,
                "user" => $user_data
            ]);
            
        } else {
            // Invalid password
            if (config('app.debug')) {
                error_log("Password verification failed for user: $username");
            }
            
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "message" => "Invalid username or password"
            ]);
        }
    } else {
        // User not found
        if (config('app.debug')) {
            error_log("User not found: $username");
        }
        
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid username or password"
        ]);
    }
    
} catch (PDOException $e) {
    // Database-specific errors
    error_log("Database error in auth/login.php: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => config('app.debug') ? "Database error: " . $e->getMessage() : "A database error occurred",
        "debug_info" => config('app.debug') ? [
            "sql_state" => $e->getCode(),
            "error_info" => $e->errorInfo ?? null
        ] : null
    ]);
    
} catch (Exception $e) {
    // General application errors
    error_log("Error in auth/login.php: " . $e->getMessage());
    
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