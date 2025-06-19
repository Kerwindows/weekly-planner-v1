<?php
/**
 * Weekly Planner Setup Script
 * Helps configure the environment and database
 */

echo "🚀 Weekly Planner Setup Script\n";
echo "================================\n\n";

// Check if .env file exists
if (!file_exists('.env')) {
    echo "📝 Creating .env file from template...\n";
    
    if (file_exists('.env.example')) {
        copy('.env.example', '.env');
        echo "✅ .env file created successfully!\n";
        echo "⚠️  Please edit .env file with your actual configuration values.\n\n";
    } else {
        echo "❌ .env.example file not found!\n";
        echo "Please create .env.example first.\n\n";
        exit(1);
    }
} else {
    echo "✅ .env file already exists.\n\n";
}

// Load environment variables
function loadEnv($path = '.env') {
    if (!file_exists($path)) return;
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

loadEnv();

// Get environment variable
function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

// Database configuration check
echo "🗄️  Checking database configuration...\n";

$dbHost = env('DB_HOST', 'localhost');
$dbPort = env('DB_PORT', '3306');
$dbName = env('DB_DATABASE', 'weekly_planner');
$dbUser = env('DB_USERNAME', '');
$dbPass = env('DB_PASSWORD', '');

if (empty($dbUser) || empty($dbPass)) {
    echo "⚠️  Database credentials not configured in .env file.\n";
    echo "Please update DB_USERNAME and DB_PASSWORD in .env file.\n\n";
} else {
    echo "✅ Database configuration found.\n\n";
}

// Test database connection
echo "🔌 Testing database connection...\n";

try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Database server connection successful.\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database '$dbName' already exists.\n";
    } else {
        echo "📝 Creating database '$dbName'...\n";
        $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Database '$dbName' created successfully.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database configuration in .env file.\n\n";
}

// Create database tables
echo "\n📋 Creating database tables...\n";

$dbDsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";

try {
    $pdo = new PDO($dbDsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            time_start INT DEFAULT 6,
            time_end INT DEFAULT 18,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Weekly planners table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS weekly_planners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            week_start_date DATE NOT NULL,
            main_goal TEXT,
            secondary_goal_1 TEXT,
            secondary_goal_2 TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_week (user_id, week_start_date)
        )
    ");
    
    // Weekly goals table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS weekly_goals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            planner_id INT NOT NULL,
            goal_number INT NOT NULL,
            description TEXT,
            is_completed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (planner_id) REFERENCES weekly_planners(id) ON DELETE CASCADE,
            UNIQUE KEY unique_planner_goal (planner_id, goal_number)
        )
    ");
    
    // Daily tasks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS daily_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            planner_id INT NOT NULL,
            day_of_week ENUM('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') NOT NULL,
            task_type ENUM('most_important', 'secondary') NOT NULL,
            task_number INT NOT NULL,
            description TEXT,
            status ENUM('pending', 'completed', 'deferred', 'cancelled') DEFAULT 'pending',
            priority ENUM('none', 'low', 'medium', 'high') DEFAULT 'none',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (planner_id) REFERENCES weekly_planners(id) ON DELETE CASCADE,
            UNIQUE KEY unique_task (planner_id, day_of_week, task_type, task_number)
        )
    ");
    
    // Time blocks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS time_blocks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            planner_id INT NOT NULL,
            day_of_week ENUM('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') NOT NULL,
            hour INT NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (planner_id) REFERENCES weekly_planners(id) ON DELETE CASCADE,
            UNIQUE KEY unique_time_block (planner_id, day_of_week, hour)
        )
    ");
    
    // Nightly recaps table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS nightly_recaps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            planner_id INT NOT NULL,
            day_of_week ENUM('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') NOT NULL,
            notes TEXT,
            prompt_1_response TEXT,
            prompt_2_response TEXT,
            prompt_3_response TEXT,
            tasks_completed INT DEFAULT 0,
            tasks_deferred INT DEFAULT 0,
            tasks_cancelled INT DEFAULT 0,
            tasks_incomplete INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (planner_id) REFERENCES weekly_planners(id) ON DELETE CASCADE,
            UNIQUE KEY unique_recap (planner_id, day_of_week)
        )
    ");
    
    echo "✅ All database tables created successfully.\n";
    
} catch (PDOException $e) {
    echo "❌ Error creating tables: " . $e->getMessage() . "\n";
}

// Create necessary directories
echo "\n📁 Creating necessary directories...\n";

$directories = [
    'logs',
    'uploads',
    'backups',
    'cache',
    'storage'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✅ Created directory: $dir\n";
    } else {
        echo "✅ Directory already exists: $dir\n";
    }
    
    // Create .gitkeep file to preserve empty directories
    $gitkeep = "$dir/.gitkeep";
    if (!file_exists($gitkeep)) {
        touch($gitkeep);
    }
}

// Set proper permissions
echo "\n🔐 Setting directory permissions...\n";

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        chmod($dir, 0755);
        echo "✅ Set permissions for: $dir\n";
    }
}

// Generate JWT secret if not set
echo "\n🔑 Checking JWT configuration...\n";

$jwtSecret = env('JWT_SECRET');
if (empty($jwtSecret) || $jwtSecret === 'your-super-secret-jwt-key' || $jwtSecret === 'generate-a-strong-random-key-here') {
    echo "🔐 Generating JWT secret...\n";
    $newSecret = bin2hex(random_bytes(32));
    
    // Update .env file
    $envContent = file_get_contents('.env');
    $envContent = preg_replace('/JWT_SECRET=.*/', "JWT_SECRET=$newSecret", $envContent);
    file_put_contents('.env', $envContent);
    
    echo "✅ JWT secret generated and saved to .env file.\n";
} else {
    echo "✅ JWT secret is already configured.\n";
}

// Check file permissions
echo "\n📋 Checking file permissions...\n";

$files = ['.env', 'config.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        chmod($file, 0600); // Read/write for owner only
        echo "✅ Set secure permissions for: $file\n";
    }
}

// Configuration summary
echo "\n📊 Configuration Summary\n";
echo "========================\n";
echo "Environment: " . env('APP_ENV', 'development') . "\n";
echo "Database Host: $dbHost:$dbPort\n";
echo "Database Name: $dbName\n";
echo "API URL: " . env('API_URL_DEV', 'Not set') . "\n";
echo "Frontend URL: " . env('FRONTEND_URL_DEV', 'Not set') . "\n";

// Final instructions
echo "\n🎉 Setup Complete!\n";
echo "==================\n";
echo "Next steps:\n";
echo "1. Review and update your .env file with actual values\n";
echo "2. Update the API URLs in your Vue.js config\n";
echo "3. Test your application\n";
echo "4. For production: set APP_ENV=production in .env\n\n";

echo "📚 Important files:\n";
echo "- .env (your environment configuration)\n";
echo "- config.php (PHP configuration loader)\n";
echo "- config.js (Frontend configuration)\n\n";

echo "🔒 Security reminder:\n";
echo "- Never commit .env file to version control\n";
echo "- Use strong passwords and JWT secrets\n";
echo "- Enable HTTPS in production\n\n";

echo "Happy planning! 📅✨\n";
?>