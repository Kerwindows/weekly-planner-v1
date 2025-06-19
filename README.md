# Weekly Planner Web Application

A modern, responsive weekly planner application built with Vue.js, PHP, and MySQL. Features environment-based configuration, enhanced security, and comprehensive task management.

## Features

- **User Authentication**: Secure JWT-based login and registration with rate limiting
- **Weekly Planning**: Set main and secondary goals for each week with progress tracking
- **Daily Task Management**: Track most important and secondary tasks with priority levels
- **Time Blocking**: Hour-by-hour scheduling with customizable time ranges
- **Task Status Tracking**: Mark tasks as completed, deferred, cancelled, or incomplete
- **Nightly Recap**: Reflect on daily progress with guided prompts and statistics
- **Analytics**: Comprehensive completion rates and progress tracking
- **Archive System**: Access and review previous weeks' planners
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile devices
- **Dark/Light Theme**: Toggle between themes with user preference saving
- **Auto-save**: All changes are saved automatically with real-time feedback
- **Environment Configuration**: Secure, flexible configuration system

## Prerequisites

- **Web Server**: Apache 2.4+ or Nginx
- **PHP**: 7.4 or higher (8.0+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Extensions**: PHP extensions: PDO, PDO_MySQL, JSON, OpenSSL

## Installation

### 1. Clone or Download

```bash
git clone <repository-url> weekly-planner
cd weekly-planner
```

### 2. Environment Configuration

1. **Copy environment template**:
```bash
cp .env.example .env
```

2. **Configure your environment** by editing `.env`:
```env
# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_NAME="Weekly Planner"

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=weekly_planner
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password
DB_CHARSET=utf8mb4

# API Configuration
API_URL_PROD=https://your-domain.com/api

# Security
JWT_SECRET=your-super-secure-jwt-secret-key
PASSWORD_HASH_COST=12

# Email Configuration (optional)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password

# Features
FEATURE_DRAG_DROP=true
FEATURE_ANALYTICS=true
FEATURE_DARK_MODE=true
```

### 3. Database Setup

1. **Create database**:
```sql
CREATE DATABASE weekly_planner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Run the setup script**:
```bash
php setup.php
```

This will:
- Create all necessary database tables
- Set up proper file permissions
- Generate secure JWT secrets
- Create required directories

### 4. Web Server Configuration

#### Apache Setup

1. **Enable required modules**:
```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

2. **Virtual Host Configuration**:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/weekly-planner
    
    <Directory /var/www/html/weekly-planner>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        # CORS Headers
        Header always set Access-Control-Allow-Origin "*"
        Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
        Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
        Header always set Access-Control-Allow-Credentials "true"
    </Directory>
    
    # Security Headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

#### Nginx Setup

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/weekly-planner;
    index index.html index.php;

    # CORS Headers
    add_header Access-Control-Allow-Origin "*" always;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With" always;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }
}
```

### 5. Frontend Configuration

1. **Update configuration** in your HTML file:
```html
<script src="config.js"></script>
<script>
    // Configure axios with environment-aware URL
    axios.defaults.baseURL = WeeklyPlannerConfig.API.BASE_URL;
    axios.defaults.timeout = WeeklyPlannerConfig.API.TIMEOUT;
</script>
```

2. **Or use inline configuration**:
```html
<script>
window.WeeklyPlannerConfig = {
    API: {
        BASE_URL: window.location.hostname === 'localhost' ? 
            'http://localhost/weekly-planner/api' : 
            'https://your-domain.com/api'
    }
};
</script>
```

## Project Structure

```
weekly-planner/
├── api/                     # Backend API endpoints
│   ├── auth/
│   │   ├── login.php       # User authentication
│   │   ├── register.php    # User registration
│   │   └── validate.php    # Token validation
│   ├── planner/
│   │   ├── create.php      # Create new planner
│   │   └── get.php         # Retrieve planner data
│   ├── goals/
│   │   └── update.php      # Update weekly goals
│   ├── tasks/
│   │   └── update.php      # Update daily tasks
│   ├── timeblock/
│   │   └── update.php      # Update time blocks
│   ├── recap/
│   │   └── update.php      # Update nightly recaps
│   └── user/
│       └── profile.php     # User profile management
├── config/
│   ├── database.php        # Database connection class
│   └── cors.php           # CORS configuration
├── helpers/
│   └── auth.php           # Authentication helper
├── config.php             # Main configuration loader
├── config.js              # Frontend configuration
├── setup.php              # Database setup script
├── .env                   # Environment variables
├── .env.example           # Environment template
├── .gitignore            # Git ignore rules
└── index.html            # Frontend application
```

## Usage

### Getting Started

1. **Access the application** at `https://your-domain.com`

2. **Register** a new account with:
   - Username (3-50 characters, alphanumeric + dots/hyphens/underscores)
   - Valid email address
   - Password (minimum 6 characters, configurable)

3. **Login** with your credentials to receive a JWT token

### Weekly Planning

1. **Set Weekly Goals**:
   - Main goal (primary focus for the week)
   - Secondary goals (supporting objectives)
   - Break down into actionable steps (up to 10)

2. **Daily Task Management**:
   - **Most Important Tasks**: Up to 2 critical tasks per day
   - **Secondary Tasks**: Up to 2 supporting tasks per day
   - **Status Tracking**: ✓ (Completed), → (Deferred), ✕ (Cancelled)
   - **Priority Levels**: None, Low, Medium, High

3. **Time Blocking**:
   - Schedule activities for each hour
   - Customize working hours (default: 6 AM - 6 PM)
   - Visual time management

4. **Nightly Recap**:
   - Reflect on daily achievements
   - Identify improvement areas
   - Plan tomorrow's priority
   - View completion statistics

### Navigation & Features

- **Week Navigation**: Arrow buttons to move between weeks
- **Archive Access**: View and review past weeks
- **Theme Toggle**: Switch between light and dark modes
- **Auto-save**: Changes saved automatically
- **Statistics**: Real-time progress tracking
- **Mobile Responsive**: Optimized for all devices

## Security Features

### Authentication & Authorization
- **JWT Tokens**: Secure, stateless authentication
- **Rate Limiting**: Prevents brute force attacks
- **Password Hashing**: Bcrypt with configurable cost
- **Input Validation**: Comprehensive server-side validation

### Data Protection
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Prevention**: Input sanitization and output encoding
- **CSRF Protection**: Token-based request validation
- **Environment Variables**: Secure credential management

### Security Headers
- **HSTS**: HTTP Strict Transport Security
- **CSP**: Content Security Policy
- **X-Frame-Options**: Clickjacking protection
- **X-Content-Type-Options**: MIME type sniffing protection

## Configuration Options

### Environment Variables

```env
# Validation Settings
VALIDATION_PASSWORD_MIN_LENGTH=8
VALIDATION_GOAL_MAX_LENGTH=500
AUTH_REQUIRE_STRONG_PASSWORD=false

# Rate Limiting
RATE_LIMIT_AUTH=10
RATE_LIMIT_API=100

# Security Headers
SECURITY_ENABLE_HSTS=true
SECURITY_ENABLE_CSP=true
SECURITY_ENABLE_XSS_PROTECTION=true

# CORS Configuration
CORS_ALLOWED_ORIGINS=["https://your-domain.com"]
CORS_ALLOWED_METHODS=["GET","POST","PUT","DELETE","OPTIONS"]

# Feature Flags
FEATURE_DRAG_DROP=true
FEATURE_ANALYTICS=true
FEATURE_DARK_MODE=true

# Default User Settings
DATETIME_DEFAULT_WORK_START=6
DATETIME_DEFAULT_WORK_END=18
```

### Database Optimization

```sql
-- Add indexes for better performance
ALTER TABLE weekly_planners ADD INDEX idx_user_week (user_id, week_start_date);
ALTER TABLE daily_tasks ADD INDEX idx_planner_day (planner_id, day_of_week);
ALTER TABLE time_blocks ADD INDEX idx_planner_day_hour (planner_id, day_of_week, hour);
```

## API Documentation

### Authentication Endpoints

```http
POST /api/auth/register.php
Content-Type: application/json

{
    "username": "testuser",
    "email": "user@example.com",
    "password": "securepassword",
    "time_start": 7,
    "time_end": 19
}
```

```http
POST /api/auth/login.php
Content-Type: application/json

{
    "username": "testuser",
    "password": "securepassword"
}
```

### Data Endpoints

All data endpoints require authentication:
```http
Authorization: Bearer <jwt_token>
```

- `GET /api/planner/get.php?week_start_date=2025-01-19`
- `POST /api/goals/update.php`
- `POST /api/tasks/update.php`
- `POST /api/timeblock/update.php`
- `POST /api/recap/update.php`

## Troubleshooting

### Common Issues

1. **Configuration Errors**:
   ```bash
   # Check configuration
   php -f setup.php
   
   # Validate environment
   php -r "require 'config.php'; var_dump(config('app.env'));"
   ```

2. **Database Connection**:
   ```bash
   # Test database connection
   php -f debug_db_structure.php
   ```

3. **Authentication Issues**:
   ```javascript
   // Debug authentication in browser console
   console.log('Token:', localStorage.getItem('weekly-planner-auth-token'));
   console.log('Axios headers:', axios.defaults.headers.common);
   ```

4. **CORS Problems**:
   - Verify web server CORS configuration
   - Check browser console for CORS errors
   - Ensure `Access-Control-Allow-*` headers are set

### Performance Optimization

1. **Database**:
   - Add indexes on frequently queried columns
   - Use connection pooling
   - Optimize query performance

2. **Frontend**:
   - Enable gzip compression
   - Implement service workers for caching
   - Optimize image assets

3. **Security**:
   - Use HTTPS in production
   - Implement proper session management
   - Regular security audits

## Development vs Production

### Development Setup
```env
APP_ENV=development
APP_DEBUG=true
API_URL_DEV=http://localhost/weekly-planner/api
```

### Production Setup
```env
APP_ENV=production
APP_DEBUG=false
API_URL_PROD=https://your-domain.com/api
SECURITY_ENABLE_HSTS=true
```

## Backup & Maintenance

### Database Backup
```bash
# Create backup
mysqldump -u username -p weekly_planner > backup_$(date +%Y%m%d).sql

# Restore backup
mysql -u username -p weekly_planner < backup_20250120.sql
```

### Log Management
```bash
# View error logs
tail -f /var/log/apache2/error.log
tail -f storage/logs/app.log
```

## Future Enhancements

- **Progressive Web App (PWA)**: Offline functionality and app-like experience
- **Email Notifications**: Reminders and progress reports
- **Analytics Dashboard**: Detailed insights and trends
- **Export Features**: PDF reports and data export
- **Team Collaboration**: Shared planners and team features
- **API Rate Limiting**: Advanced rate limiting with Redis
- **Real-time Updates**: WebSocket integration for live updates
- **Mobile Applications**: Native iOS and Android apps

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is provided under the MIT License for personal and commercial use.

## Support

For issues and support:
1. Check the troubleshooting section
2. Review error logs
3. Create an issue with detailed information
4. Include environment details and error messages