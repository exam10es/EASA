<?php
/**
 * Admin Logout
 * Destroys admin session and redirects to login
 */

require_once __DIR__ . '/../includes/functions.php';

// Log the logout activity
if (isAdminLoggedIn()) {
    logAdminActivity('logout');
}

// Destroy session
session_destroy();

// Clear remember me cookie
setcookie('admin_remember', '', time() - 3600, '/');

// Redirect to login
redirect('login.php');