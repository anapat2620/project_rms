<?php
// Configuration file for the research management system

// Debug mode - set to false in production
define('DEBUG_MODE', false);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'research_db');

// Timezone
date_default_timezone_set('Asia/Bangkok');

// Session configuration (only before session_start)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
}

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx']);

// Approver positions
define('APPROVER_POSITIONS', ['อธิการบดี', 'รองอธิการบดี', 'ผู้ช่วยอธิการบดี']);

// Helper functions
function isApprover($position) {
    return in_array($position, APPROVER_POSITIONS);
}

function logDebug($message, $data = null) {
    if (DEBUG_MODE) {
        $logMessage = date('Y-m-d H:i:s') . " - " . $message;
        if ($data !== null) {
            $logMessage .= " - " . print_r($data, true);
        }
        $logMessage .= "\n";
        file_put_contents(__DIR__ . '/debug.log', $logMessage, FILE_APPEND);
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Note: no closing PHP tag to prevent accidental whitespace output (which can break JSON/header responses).