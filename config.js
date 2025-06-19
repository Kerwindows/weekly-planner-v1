/**
 * Weekly Planner Frontend Configuration
 * Handles environment-based configuration for the Vue.js frontend
 */

// Detect environment
const getEnvironment = () => {
    // Check if we're in development (localhost)
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        return 'development';
    }
    
    // Check if we're on staging domain
    if (window.location.hostname.includes('staging')) {
        return 'staging';
    }
    
    // Default to production
    return 'production';
};

// Environment-specific configuration
const environments = {
    development: {
        API_BASE_URL: 'http://localhost/weekly-planner/api',
        API_TIMEOUT: 10000,
        DEBUG: true,
        LOG_LEVEL: 'debug',
        ENABLE_DEVTOOLS: true,
        ENABLE_HOT_RELOAD: true,
    },
    
    staging: {
        API_BASE_URL: 'https://staging.yourdomain.com/api',
        API_TIMEOUT: 15000,
        DEBUG: true,
        LOG_LEVEL: 'info',
        ENABLE_DEVTOOLS: true,
        ENABLE_HOT_RELOAD: false,
    },
    
    production: {
        API_BASE_URL: 'https://weekly-planner.kerwindows.com/api',
        API_TIMEOUT: 20000,
        DEBUG: false,
        LOG_LEVEL: 'error',
        ENABLE_DEVTOOLS: false,
        ENABLE_HOT_RELOAD: false,
    }
};

// Get current environment
const currentEnv = getEnvironment();

// Base configuration
const config = {
    // Environment info
    ENVIRONMENT: currentEnv,
    VERSION: '1.0.0',
    
    // API Configuration
    API: {
        BASE_URL: environments[currentEnv].API_BASE_URL,
        TIMEOUT: environments[currentEnv].API_TIMEOUT,
        ENDPOINTS: {
            AUTH: {
                LOGIN: '/auth/login.php',
                REGISTER: '/auth/register.php',
                LOGOUT: '/auth/logout.php',
                REFRESH: '/auth/refresh.php',
                PROFILE: '/auth/profile.php'
            },
            PLANNER: {
                GET: '/planner/get.php',
                CREATE: '/planner/create.php',
                UPDATE: '/planner/update.php',
                DELETE: '/planner/delete.php'
            },
            GOALS: {
                UPDATE: '/goals/update.php',
                DELETE: '/goals/delete.php'
            },
            TASKS: {
                UPDATE: '/tasks/update.php',
                BULK_UPDATE: '/tasks/bulk-update.php',
                DELETE: '/tasks/delete.php'
            },
            TIME_BLOCKS: {
                UPDATE: '/timeblock/update.php',
                DELETE: '/timeblock/delete.php'
            },
            RECAPS: {
                UPDATE: '/recap/update.php',
                GET: '/recap/get.php'
            },
            USER: {
                UPDATE_SETTINGS: '/user/update-settings.php',
                GET_PROFILE: '/user/profile.php'
            }
        }
    },
    
    // Application Features
    FEATURES: {
        DRAG_DROP: true,
        ANALYTICS: true,
        DARK_MODE: true,
        KEYBOARD_SHORTCUTS: true,
        AUTO_SAVE: true,
        OFFLINE_MODE: false,
        EXPORT_PDF: false,
        CALENDAR_SYNC: false,
        TEAM_COLLABORATION: false,
        NOTIFICATIONS: true
    },
    
    // UI Configuration
    UI: {
        THEME: {
            DEFAULT: 'light',
            STORAGE_KEY: 'weekly-planner-theme'
        },
        ANIMATIONS: {
            ENABLED: true,
            DURATION: 300
        },
        AUTO_SAVE_DELAY: 1000, // milliseconds
        TOAST_DURATION: 3000,
        LOADING_TIMEOUT: 30000
    },
    
    // Local Storage Keys
    STORAGE_KEYS: {
        AUTH_TOKEN: 'weekly-planner-auth-token',
        USER_DATA: 'weekly-planner-user',
        THEME: 'weekly-planner-theme',
        LAST_SYNC: 'weekly-planner-last-sync',
        OFFLINE_DATA: 'weekly-planner-offline-data',
        USER_PREFERENCES: 'weekly-planner-preferences'
    },
    
    // Date and Time Configuration
    DATETIME: {
        DEFAULT_TIMEZONE: 'America/New_York',
        DATE_FORMAT: 'YYYY-MM-DD',
        TIME_FORMAT: 'HH:mm',
        DATETIME_FORMAT: 'YYYY-MM-DD HH:mm:ss',
        WEEK_STARTS_ON: 0, // 0 = Sunday, 1 = Monday
        DEFAULT_WORK_START: 6,
        DEFAULT_WORK_END: 18
    },
    
    // Validation Rules
    VALIDATION: {
        PASSWORD_MIN_LENGTH: 8,
        USERNAME_MIN_LENGTH: 3,
        USERNAME_MAX_LENGTH: 50,
        GOAL_MAX_LENGTH: 500,
        TASK_MAX_LENGTH: 200,
        NOTES_MAX_LENGTH: 2000,
        MAX_WEEKLY_GOALS: 10,
        MAX_DAILY_TASKS: 4
    },
    
    // Performance Settings
    PERFORMANCE: {
        DEBOUNCE_DELAY: 300,
        THROTTLE_DELAY: 100,
        MAX_RETRY_ATTEMPTS: 3,
        RETRY_DELAY: 1000,
        CACHE_TTL: 300000, // 5 minutes
        MAX_CONCURRENT_REQUESTS: 5
    },
    
    // Analytics Configuration
    ANALYTICS: {
        ENABLED: environments[currentEnv].DEBUG === false,
        GOOGLE_ANALYTICS_ID: 'GA-XXXXXXXXX',
        TRACK_PAGE_VIEWS: true,
        TRACK_USER_INTERACTIONS: true,
        TRACK_PERFORMANCE: true
    },
    
    // Error Handling
    ERROR_HANDLING: {
        LOG_LEVEL: environments[currentEnv].LOG_LEVEL,
        SHOW_STACK_TRACE: environments[currentEnv].DEBUG,
        AUTO_REPORT_ERRORS: !environments[currentEnv].DEBUG,
        MAX_ERROR_LOGS: 100
    },
    
    // Development Tools
    DEVELOPMENT: {
        DEBUG: environments[currentEnv].DEBUG,
        ENABLE_DEVTOOLS: environments[currentEnv].ENABLE_DEVTOOLS,
        ENABLE_HOT_RELOAD: environments[currentEnv].ENABLE_HOT_RELOAD,
        MOCK_API: false,
        LOG_API_CALLS: environments[currentEnv].DEBUG
    },
    
    // Security Configuration
    SECURITY: {
        TOKEN_REFRESH_THRESHOLD: 5 * 60 * 1000, // 5 minutes before expiry
        MAX_LOGIN_ATTEMPTS: 5,
        LOCKOUT_DURATION: 15 * 60 * 1000, // 15 minutes
        SECURE_COOKIES: currentEnv === 'production',
        CSP_ENABLED: currentEnv === 'production'
    },
    
    // Keyboard Shortcuts
    KEYBOARD_SHORTCUTS: {
        TOGGLE_THEME: 't',
        QUICK_SAVE: 'ctrl+s',
        NEW_TASK: 'ctrl+n',
        DUPLICATE_YESTERDAY: 'ctrl+d',
        MARK_COMPLETE: 'ctrl+enter',
        SHOW_HELP: '?',
        NEXT_DAY: 'j',
        PREV_DAY: 'k',
        TOGGLE_DAY: ' '
    },
    
    // Notification Settings
    NOTIFICATIONS: {
        ENABLED: true,
        TYPES: {
            SUCCESS: 'success',
            ERROR: 'error',
            WARNING: 'warning',
            INFO: 'info'
        },
        POSITION: 'top-right',
        AUTO_DISMISS: true,
        DISMISS_DELAY: 5000
    }
};

// Utility functions
const ConfigUtils = {
    // Get configuration value with dot notation
    get(path, defaultValue = null) {
        return path.split('.').reduce((obj, key) => 
            (obj && obj[key] !== undefined) ? obj[key] : defaultValue, config
        );
    },
    
    // Check if feature is enabled
    isFeatureEnabled(feature) {
        return this.get(`FEATURES.${feature.toUpperCase()}`, false);
    },
    
    // Get API endpoint URL
    getApiUrl(endpoint) {
        const baseUrl = this.get('API.BASE_URL');
        return `${baseUrl}${endpoint}`;
    },
    
    // Get full API endpoint path
    getApiEndpoint(category, action) {
        const endpoint = this.get(`API.ENDPOINTS.${category.toUpperCase()}.${action.toUpperCase()}`);
        return endpoint ? this.getApiUrl(endpoint) : null;
    },
    
    // Check if in development mode
    isDevelopment() {
        return this.get('ENVIRONMENT') === 'development';
    },
    
    // Check if debug mode is enabled
    isDebugMode() {
        return this.get('DEVELOPMENT.DEBUG', false);
    },
    
    // Get storage key
    getStorageKey(key) {
        return this.get(`STORAGE_KEYS.${key.toUpperCase()}`);
    },
    
    // Get keyboard shortcut
    getShortcut(action) {
        return this.get(`KEYBOARD_SHORTCUTS.${action.toUpperCase()}`);
    },
    
    // Validate configuration
    validate() {
        const requiredKeys = [
            'API.BASE_URL',
            'STORAGE_KEYS.AUTH_TOKEN',
            'DATETIME.DEFAULT_TIMEZONE'
        ];
        
        const missing = requiredKeys.filter(key => !this.get(key));
        
        if (missing.length > 0) {
            console.error('Missing required configuration keys:', missing);
            return false;
        }
        
        return true;
    },
    
    // Log configuration info (development only)
    logInfo() {
        if (this.isDebugMode()) {
            console.group('🔧 Weekly Planner Configuration');
            console.log('Environment:', this.get('ENVIRONMENT'));
            console.log('API Base URL:', this.get('API.BASE_URL'));
            console.log('Debug Mode:', this.get('DEVELOPMENT.DEBUG'));
            console.log('Features:', this.get('FEATURES'));
            console.groupEnd();
        }
    }
};

// Export configuration and utilities
window.WeeklyPlannerConfig = config;
window.ConfigUtils = ConfigUtils;

// Initialize configuration
document.addEventListener('DOMContentLoaded', () => {
    // Validate configuration
    if (!ConfigUtils.validate()) {
        console.error('❌ Configuration validation failed');
        return;
    }
    
    // Log configuration info in development
    ConfigUtils.logInfo();
    
    // Set up global error handling if enabled
    if (config.ERROR_HANDLING.AUTO_REPORT_ERRORS) {
        window.addEventListener('error', (event) => {
            console.error('Global error caught:', event.error);
            // Here you could send to error reporting service
        });
        
        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
            // Here you could send to error reporting service
        });
    }
    
    // Initialize theme if available
    const savedTheme = localStorage.getItem(config.STORAGE_KEYS.THEME);
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
    
    console.log('✅ Weekly Planner configuration initialized');
});

// Export for ES6 modules (if using module system)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { config, ConfigUtils };
}

// Export for AMD (if using RequireJS)
if (typeof define === 'function' && define.amd) {
    define(() => ({ config, ConfigUtils }));
}