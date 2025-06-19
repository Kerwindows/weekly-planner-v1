<?php
/**
 * CORS Configuration (Legacy)
 * This file is kept for backward compatibility but no longer defines functions
 * All CORS functionality has been moved to config.php
 */

// Include main configuration if not already loaded
if (!function_exists('config')) {
    require_once dirname(__DIR__) . '/config.php';
}

// Auto-execute CORS headers when this file is included (for backward compatibility)
setCorsHeaders();

// Note: The setCorsHeaders() function is now defined in config.php
// This file just calls it for backward compatibility with existing code
?>