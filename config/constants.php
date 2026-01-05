<?php
/**
 * Site Constants Template
 * This file contains global constants for the website
 * Will be configured by the installer
 */

// Site Information
define('SITE_NAME', 'Examination Website');
define('SITE_URL', 'http://your-domain.com/');
define('ADMIN_URL', SITE_URL . 'admin/');
define('ASSETS_URL', SITE_URL . 'assets/');
define('UPLOADS_URL', SITE_URL . 'uploads/');

// File Paths
define('ROOT_PATH', __DIR__ . '/../');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('ADMIN_PATH', ROOT_PATH . 'admin/');
define('PUBLIC_PATH', ROOT_PATH . 'public/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');

// Exam Settings
define('EXAM_TIMER_ENABLED', true);
define('EXAM_TIMER_DURATION', 30); // minutes
define('SHOW_EXPLANATIONS', true);
define('ALLOW_RETAKES', true);
define('PASSING_PERCENTAGE', 70);

// Security Settings
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes in seconds

// File Upload Settings
define('MAX_UPLOAD_SIZE', 2097152); // 2MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Pagination Settings
define('ITEMS_PER_PAGE', 20);
define('QUESTIONS_PER_PAGE', 50);

// Error Reporting
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}