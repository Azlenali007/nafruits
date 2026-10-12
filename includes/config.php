<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Application Configuration
 */

declare(strict_types=1);

// Error Reporting (Environment-safe for production/staging)
if (file_exists(__DIR__ . '/../.dev_mode')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Session Configuration (Strict, secure cookie handling)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// Database Credentials (Can be overridden by /config.local.php created by /install/)
$localConfigFile = __DIR__ . '/../config.local.php';
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
} else {
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_NAME', getenv('DB_NAME') ?: 'nafresh_db');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_CHARSET', 'utf8mb4');
}

// Application Constants
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Na Fresh – Fruits & Coconuts');
}
if (!defined('APP_TAGLINE')) {
    define('APP_TAGLINE', 'Freshness in Every Bite');
}
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', '₹');
}

// Path Constants
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', __DIR__);
define('UPLOAD_PATH', ROOT_PATH . '/uploads');

// Autoload core helpers
require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';
