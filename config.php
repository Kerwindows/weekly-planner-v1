<?php
/**
 * Weekly Planner Configuration File
 * Loads environment variables and provides configuration settings
 */

// Load environment variables from .env file
function loadEnv($path = __DIR__ . '/.env') {
    if (!file_exists($path)) {
        throw new Exception('.env file not found at: ' . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Set environment variable
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Load the .env file
try {
    loadEnv();
} catch (Exception $e) {
    // In production, you might want to handle this differently
    error_log("Environment configuration error: " . $e->getMessage());
}

// Helper function to get environment variables with default values
function env($key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key) ?? $default;
    
    // Convert string boolean values to actual booleans
    if (is_string($value)) {
        switch (strtolower($value)) {
            case 'true':
            case '1':
            case 'yes':
            case 'on':
                return true;
            case 'false':
            case '0':
            case 'no':
            case 'off':
            case '':
                return false;
        }
    }
    
    return $value;
}

// Configuration array
$config = [
    // Application settings
    'app' => [
        'env' => env('APP_ENV', 'development'),
        'debug' => env('APP_DEBUG', true),
        'name' => env('APP_NAME', 'Weekly Planner'),
        'version' => env('APP_VERSION', '1.0.0'),
        'timezone' => env('DEFAULT_TIMEZONE', 'America/New_York'),
    ],

    // Database configuration
    'database' => [
        'type' => env('DB_TYPE', 'mysql'),
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'weekly_planner'),
        'username' => env('DB_USERNAME', ''),
        'password' => env('DB_PASSWORD', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],

    // API configuration
    'api' => [
        'base_url' => env('API_URL', env('API_URL_DEV', 'http://localhost/weekly-planner/api')),
        'base_path' => env('API_BASE_PATH', env('API_BASE_PATH_DEV', '/weekly-planner/api')),
        'version' => env('API_VERSION', 'v1'),
        'prefix' => env('API_PREFIX', 'api/v1'),
    ],

    // Frontend configuration
    'frontend' => [
        'url' => env('FRONTEND_URL', env('FRONTEND_URL_DEV', 'http://localhost:3000')),
    ],

    // Authentication settings
    'auth' => [
        'jwt_secret' => env('JWT_SECRET', 'your-super-secret-jwt-key'),
        'jwt_expiration' => env('JWT_EXPIRATION', '24h'),
        'jwt_refresh_expiration' => env('JWT_REFRESH_EXPIRATION', '7d'),
        'password_hash_cost' => env('PASSWORD_HASH_COST', 12),
    ],

    // CORS settings
    'cors' => [
        'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')),
        'allowed_methods' => explode(',', env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,DELETE,OPTIONS')),
        'allowed_headers' => explode(',', env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With')),
    ],

    // Email configuration
    'mail' => [
        'driver' => env('MAIL_DRIVER', 'smtp'),
        'host' => env('MAIL_HOST', 'smtp.gmail.com'),
        'port' => env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@yourdomain.com'),
        'from_name' => env('MAIL_FROM_NAME', 'Weekly Planner'),
    ],

    // Logging configuration
    'logging' => [
        'level' => env('LOG_LEVEL', 'debug'),
        'file' => env('LOG_FILE', './logs/app.log'),
        'max_size' => env('LOG_MAX_SIZE', 10485760), // 10MB
        'max_files' => env('LOG_MAX_FILES', 5),
        'error_reporting' => env('ERROR_REPORTING', true),
    ],

    // Cache configuration
    'cache' => [
        'driver' => env('CACHE_DRIVER', 'file'),
        'ttl' => env('CACHE_TTL', 3600),
        'enabled' => env('ENABLE_CACHE', true),
    ],

    // Rate limiting
    'rate_limit' => [
        'api' => env('RATE_LIMIT_API', 100),
        'auth' => env('RATE_LIMIT_AUTH', 10),
        'upload' => env('RATE_LIMIT_UPLOAD', 20),
    ],

    // Feature flags
    'features' => [
        'drag_drop' => env('FEATURE_DRAG_DROP', true),
        'analytics' => env('FEATURE_ANALYTICS', true),
        'export_pdf' => env('FEATURE_EXPORT_PDF', false),
        'calendar_sync' => env('FEATURE_CALENDAR_SYNC', false),
        'team_collaboration' => env('FEATURE_TEAM_COLLABORATION', false),
        'dark_mode' => env('FEATURE_DARK_MODE', true),
    ],

    // File storage
    'storage' => [
        'driver' => env('STORAGE_DRIVER', 'local'),
        'path' => env('STORAGE_PATH', './uploads'),
        'max_file_size' => env('MAX_FILE_SIZE', 10485760), // 10MB
    ],

    // Security settings
    'security' => [
        'enable_hsts' => env('ENABLE_HSTS', true),
        'enable_csp' => env('ENABLE_CSP', true),
        'enable_xss_protection' => env('ENABLE_XSS_PROTECTION', true),
    ],

    // Maintenance mode
    'maintenance' => [
        'enabled' => env('MAINTENANCE_MODE', false),
        'message' => env('MAINTENANCE_MESSAGE', 'We are currently performing scheduled maintenance.'),
        'allowed_ips' => explode(',', env('MAINTENANCE_ALLOWED_IPS', '127.0.0.1,::1')),
    ],
];

// Set timezone
date_default_timezone_set($config['app']['timezone']);

// Function to get configuration values
function config($key, $default = null) {
    global $config;
    
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $default;
        }
    }
    
    return $value;
}

// Database connection function
function getDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dbConfig = config('database');
        
        try {
            $dsn = "{$dbConfig['type']}:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $pdo;
}

// CORS headers function
function setCorsHeaders() {
    $corsConfig = config('cors');
    
    // Get the origin of the request
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // Check if origin is allowed
    if (in_array($origin, $corsConfig['allowed_origins']) || in_array('*', $corsConfig['allowed_origins'])) {
        header("Access-Control-Allow-Origin: $origin");
    }
    
    header("Access-Control-Allow-Methods: " . implode(', ', $corsConfig['allowed_methods']));
    header("Access-Control-Allow-Headers: " . implode(', ', $corsConfig['allowed_headers']));
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400"); // 24 hours
}

// Error handling function
function handleError($errno, $errstr, $errfile, $errline) {
    if (config('logging.error_reporting')) {
        $logMessage = "Error [$errno]: $errstr in $errfile on line $errline";
        error_log($logMessage);
        
        // In development, show errors
        if (config('app.debug')) {
            echo "<pre>$logMessage</pre>";
        }
    }
}

// Set custom error handler
set_error_handler('handleError');

// Return the configuration array for use in other files
return $config;
?>