<?php
/**
 * Core Functions
 * Contains utility functions used throughout the application
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration files
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 * @param mixed $input
 * @return mixed
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to URL
 * @param string $url
 * @param int $statusCode
 */
function redirect($url, $statusCode = 302) {
    header("Location: $url", true, $statusCode);
    exit;
}

/**
 * Get base URL
 * @return string
 */
function baseUrl() {
    return SITE_URL;
}

/**
 * Get admin URL
 * @return string
 */
function adminUrl() {
    return ADMIN_URL;
}

/**
 * Get assets URL
 * @return string
 */
function assetsUrl() {
    return ASSETS_URL;
}

/**
 * Format date for display
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 * @param string $datetime
 * @return string
 */
function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

/**
 * Truncate text to specified length
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate random string
 * @param int $length
 * @return string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Upload file and return path
 * @param array $file
 * @param string $directory
 * @return string|false
 */
function uploadFile($file, $directory = 'uploads/') {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Validate file type
    $allowedTypes = ALLOWED_IMAGE_TYPES;
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    // Validate file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return false;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateRandomString(20) . '.' . $extension;
    $uploadPath = $directory . $filename;
    
    // Create directory if not exists
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return $uploadPath;
    }
    
    return false;
}

/**
 * Delete file
 * @param string $filepath
 * @return bool
 */
function deleteFile($filepath) {
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Get file URL
 * @param string $filepath
 * @return string
 */
function fileUrl($filepath) {
    return SITE_URL . $filepath;
}

/**
 * Display flash message
 * @param string $type
 * @param string $message
 */
function flashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get and clear flash messages
 * @return array
 */
function getFlashMessages() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Check if user is logged in as admin
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

/**
 * Require admin authentication
 */
function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        redirect('login.php');
    }
}

/**
 * Get current admin info
 * @return array|null
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, created_at FROM admins WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Log admin activity
 * @param string $action
 * @param string|null $tableName
 * @param int|null $recordId
 */
function logAdminActivity($action, $tableName = null, $recordId = null) {
    try {
        $pdo = getDBConnection();
        $adminId = $_SESSION['admin_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $pdo->prepare("INSERT INTO activity_log (admin_id, action, table_name, record_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$adminId, $action, $tableName, $recordId, $ipAddress, $userAgent]);
    } catch (Exception $e) {
        // Silently fail for logging errors
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Get pagination parameters
 * @param int $currentPage
 * @param int $totalItems
 * @param int $itemsPerPage
 * @return array
 */
function getPaginationParams($currentPage, $totalItems, $itemsPerPage = ITEMS_PER_PAGE) {
    $totalPages = max(1, ceil($totalItems / $itemsPerPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'limit' => $itemsPerPage,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Display pagination HTML
 * @param array $pagination
 * @param string $baseUrl
 * @return string
 */
function displayPagination($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<nav class="pagination">';
    $html .= '<ul class="pagination-list">';
    
    // Previous button
    if ($pagination['has_previous']) {
        $prevUrl = $baseUrl . '?page=' . ($pagination['current_page'] - 1);
        $html .= '<li><a href="' . $prevUrl . '" class="pagination-link">&laquo; Previous</a></li>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<li><span class="pagination-current">' . $i . '</span></li>';
        } else {
            $pageUrl = $baseUrl . '?page=' . $i;
            $html .= '<li><a href="' . $pageUrl . '" class="pagination-link">' . $i . '</a></li>';
        }
    }
    
    // Next button
    if ($pagination['has_next']) {
        $nextUrl = $baseUrl . '?page=' . ($pagination['current_page'] + 1);
        $html .= '<li><a href="' . $nextUrl . '" class="pagination-link">Next &raquo;</a></li>';
    }
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Calculate exam score
 * @param array $userAnswers
 * @param array $correctAnswers
 * @return array
 */
function calculateScore($userAnswers, $correctAnswers) {
    $correct = 0;
    $total = count($correctAnswers);
    
    foreach ($correctAnswers as $questionId => $correctAnswer) {
        if (isset($userAnswers[$questionId]) && $userAnswers[$questionId] === $correctAnswer) {
            $correct++;
        }
    }
    
    $percentage = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
    
    return [
        'correct' => $correct,
        'total' => $total,
        'percentage' => $percentage,
        'wrong' => $total - $correct
    ];
}

/**
 * Get exam timer settings
 * @return array
 */
function getExamTimerSettings() {
    return [
        'enabled' => EXAM_TIMER_ENABLED,
        'duration' => EXAM_TIMER_DURATION * 60 // Convert to seconds
    ];
}

/**
 * Format file size
 * @param int $bytes
 * @return string
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate unique slug
 * @param string $text
 * @param string $table
 * @param string $column
 * @return string
 */
function generateSlug($text, $table = null, $column = 'slug') {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
    
    if ($table && $column) {
        try {
            $pdo = getDBConnection();
            $originalSlug = $slug;
            $counter = 1;
            
            while (true) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
                $stmt->execute([$slug]);
                
                if ($stmt->fetchColumn() == 0) {
                    break;
                }
                
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
        } catch (Exception $e) {
            // If database fails, just return the original slug
        }
    }
    
    return $slug;
}

/**
 * Get setting value
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getSetting($key, $default = null) {
    static $settings = null;
    
    if ($settings === null) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
            $settings = $stmt->fetch();
        } catch (Exception $e) {
            return $default;
        }
    }
    
    return $settings[$key] ?? $default;
}

/**
 * Update setting value
 * @param string $key
 * @param mixed $value
 * @return bool
 */
function updateSetting($key, $value) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE settings SET {$key} = ?, updated_at = NOW() WHERE id = 1");
        return $stmt->execute([$value]);
    } catch (Exception $e) {
        return false;
    }
}