<?php
/**
 * Authentication Helper Class
 * Handles JWT token validation and user authentication
 * Now uses environment-based configuration
 */

// Include configuration if not already loaded
if (!function_exists('config')) {
    require_once dirname(__DIR__) . '/config.php';
}

class Auth {
    private $jwt_secret;
    private $jwt_expiration;
    
    public function __construct() {
        $this->jwt_secret = config('auth.jwt_secret');
        $this->jwt_expiration = config('auth.jwt_expiration', '24h');
        
        if (empty($this->jwt_secret)) {
            throw new Exception('JWT secret not configured. Please check your .env file.');
        }
    }
    
    /**
     * Get Bearer token from Authorization header
     */
    public function getBearerToken() {
        $headers = $this->getAuthHeaders();
        
        // Look for Authorization header
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        } else {
            return null;
        }
        
        // Extract token from "Bearer TOKEN" format
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Get authorization headers
     */
    private function getAuthHeaders() {
        $headers = [];
        
        // Try different methods to get headers
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            // Fallback: parse from $_SERVER
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $header = str_replace('_', '-', substr($key, 5));
                    $headers[$header] = $value;
                }
            }
        }
        
        return $headers;
    }
    
    /**
     * Create JWT token
     */
    public function createToken($user_id, $username = null) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload = [
            'user_id' => $user_id,
            'username' => $username,
            'iat' => time(),
            'exp' => time() + $this->parseTimeToSeconds($this->jwt_expiration)
        ];
        
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->jwt_secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * Generate JWT token (backward compatibility alias)
     */
    public function generateToken($user_id, $username = null) {
        return $this->createToken($user_id, $username);
    }
    
    /**
     * Validate JWT token and return user ID
     */
    public function validateToken($token) {
        if (empty($token)) {
            return false;
        }
        
        try {
            // Split token into parts
            $tokenParts = explode('.', $token);
            
            if (count($tokenParts) !== 3) {
                return false;
            }
            
            $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
            $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
            $signatureProvided = $tokenParts[2];
            
            // Verify signature
            $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
            
            $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->jwt_secret, true);
            $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            
            if (!hash_equals($base64Signature, $signatureProvided)) {
                error_log("JWT signature validation failed");
                return false;
            }
            
            // Decode payload
            $payloadData = json_decode($payload, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JWT payload decode error: " . json_last_error_msg());
                return false;
            }
            
            // Check expiration
            if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
                error_log("JWT token expired");
                return false;
            }
            
            // Return user ID
            return isset($payloadData['user_id']) ? (int)$payloadData['user_id'] : false;
            
        } catch (Exception $e) {
            error_log("JWT validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get token payload without validation (for debugging)
     */
    public function getTokenPayload($token) {
        if (empty($token)) {
            return null;
        }
        
        try {
            $tokenParts = explode('.', $token);
            if (count($tokenParts) !== 3) {
                return null;
            }
            
            $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
            return json_decode($payload, true);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Check if token is about to expire
     */
    public function isTokenExpiringSoon($token, $threshold = 300) {
        $payload = $this->getTokenPayload($token);
        
        if (!$payload || !isset($payload['exp'])) {
            return true;
        }
        
        return ($payload['exp'] - time()) < $threshold;
    }
    
    /**
     * Hash password using configured algorithm
     */
    public function hashPassword($password) {
        $cost = config('auth.password_hash_cost', 12);
        
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => $cost
        ]);
    }
    
    /**
     * Verify password against hash
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate secure random token (for password resets, etc.)
     */
    public function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Rate limiting check (basic implementation)
     */
    public function checkRateLimit($identifier, $limit = null, $window = 3600) {
        if ($limit === null) {
            $limit = config('rate_limit.auth', 10);
        }
        
        // Simple file-based rate limiting (you might want to use Redis in production)
        $rate_limit_file = sys_get_temp_dir() . '/auth_rate_limit_' . md5($identifier);
        
        $current_time = time();
        $attempts = [];
        
        // Read existing attempts
        if (file_exists($rate_limit_file)) {
            $data = file_get_contents($rate_limit_file);
            $attempts = json_decode($data, true) ?: [];
        }
        
        // Filter attempts within window
        $attempts = array_filter($attempts, function($timestamp) use ($current_time, $window) {
            return ($current_time - $timestamp) < $window;
        });
        
        // Check if limit exceeded
        if (count($attempts) >= $limit) {
            return false;
        }
        
        // Add current attempt
        $attempts[] = $current_time;
        
        // Save attempts
        file_put_contents($rate_limit_file, json_encode($attempts));
        
        return true;
    }
    
    /**
     * Parse time string to seconds
     */
    private function parseTimeToSeconds($time_string) {
        $time_string = trim($time_string);
        
        // Extract number and unit
        if (preg_match('/^(\d+)([smhd]?)$/', $time_string, $matches)) {
            $number = (int)$matches[1];
            $unit = $matches[2] ?: 's';
            
            switch ($unit) {
                case 's': return $number;
                case 'm': return $number * 60;
                case 'h': return $number * 3600;
                case 'd': return $number * 86400;
                default: return $number;
            }
        }
        
        // Default to 24 hours if parsing fails
        return 86400;
    }
    
    /**
     * Validate user session and return user data
     */
    public function getCurrentUser($token) {
        $user_id = $this->validateToken($token);
        
        if (!$user_id) {
            return null;
        }
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT id, username, email, time_start, time_end, created_at 
                      FROM users WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting current user: " . $e->getMessage());
            return null;
        }
    }
}
?>