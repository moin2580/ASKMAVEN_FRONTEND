<?php
/**
 * Configuration file for Hybrid Chatbot System
 * Compatible with PHP 8.0+ (XAMPP)
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com');
define('DB_PORT', '4000');
define('DB_NAME', 'hybrid_chatbot'); // TiDB Cloud free tier requires 'test' as the database name
define('DB_USER', '29CBizv35USNifh.root');
define('DB_PASS', 'mFZzDW3GRtRw2ZAL');
define('DB_CHARSET', 'utf8mb4');

// Python API configuration
define('PYTHON_API_BASE_URL', 'https://url-chatbot-backend.vercel.app');

// Security configuration
define('SECRET_KEY', 'askmaven');
define('JWT_SECRET_KEY', 'askmaven');
define('SESSION_LIFETIME', 3600 * 24); // 24 hours

// Email configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'ms66454566@gmail.com');
define('SMTP_PASSWORD', 'qspw tmvr doat lynh');
define('SMTP_FROM_EMAIL', 'ms66454566@gmail.com');
define('SMTP_FROM_NAME', 'Hybrid Chatbot System');

// Application settings
define('APP_NAME', 'Hybrid Chatbot System');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'Asia/Kolkata');
define('DATE_FORMAT', 'Y-m-d H:i:s');

// File upload settings
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['txt', 'pdf', 'doc', 'docx']);

// Pagination settings
define('ITEMS_PER_PAGE', 20);
define('CHAT_HISTORY_LIMIT', 100);

// API settings
define('API_TIMEOUT', 30); // seconds
define('MAX_RETRIES', 3);

// Security headers
define('SECURITY_HEADERS', [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin'
]);

// Set timezone
date_default_timezone_set(TIMEZONE);

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Set security headers
foreach (SECURITY_HEADERS as $header => $value) {
    header("$header: $value");
}
?>
