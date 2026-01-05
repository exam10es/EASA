<?php
/**
 * Admin Login Page
 * Handles administrator authentication
 */

require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    redirect('dashboard.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        flashMessage('error', 'Invalid request. Please try again.');
        redirect('login.php');
    }
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        flashMessage('error', 'Please enter both username and password.');
        redirect('login.php');
    }
    
    try {
        $pdo = getDBConnection();
        
        // Check for lockout
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            // Check if account is locked
            if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
                $remaining = strtotime($admin['locked_until']) - time();
                $minutes = ceil($remaining / 60);
                flashMessage('error', "Account is locked. Please try again in {$minutes} minutes.");
                redirect('login.php');
            }
            
            // Verify password
            if (password_verify($password, $admin['password'])) {
                // Reset failed attempts
                if ($admin['failed_login_attempts'] > 0) {
                    $pdo->prepare("UPDATE admins SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?")
                        ->execute([$admin['id']]);
                }
                
                // Set session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_login_time'] = time();
                
                // Set remember me cookie
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('admin_remember', $token, time() + (86400 * 30), '/'); // 30 days
                    
                    $pdo->prepare("UPDATE admins SET remember_token = ? WHERE id = ?")
                        ->execute([$token, $admin['id']]);
                }
                
                // Update last login
                $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")
                    ->execute([$admin['id']]);
                
                logAdminActivity('login');
                redirect('dashboard.php');
            } else {
                // Increment failed attempts
                $failedAttempts = $admin['failed_login_attempts'] + 1;
                $lockoutUntil = null;
                
                if ($failedAttempts >= MAX_LOGIN_ATTEMPTS) {
                    $lockoutUntil = date('Y-m-d H:i:s', time() + LOCKOUT_TIME);
                    flashMessage('error', 'Too many failed login attempts. Account locked for 15 minutes.');
                } else {
                    $remaining = MAX_LOGIN_ATTEMPTS - $failedAttempts;
                    flashMessage('error', "Invalid password. {$remaining} attempts remaining.");
                }
                
                $pdo->prepare("UPDATE admins SET failed_login_attempts = ?, locked_until = ? WHERE id = ?")
                    ->execute([$failedAttempts, $lockoutUntil, $admin['id']]);
            }
        } else {
            flashMessage('error', 'Invalid username or password.');
        }
    } catch (Exception $e) {
        flashMessage('error', 'An error occurred. Please try again later.');
        error_log("Login error: " . $e->getMessage());
    }
    
    redirect('login.php');
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Get flash messages
$flashMessages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo assetsUrl(); ?>css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1>Admin Login</h1>
                <p>Sign in to manage your examination system</p>
            </div>
            
            <?php if (!empty($flashMessages)): ?>
                <?php foreach ($flashMessages as $type => $message): ?>
                    <div class="alert alert-<?php echo $type; ?>">
                        <i class="fas fa-<?php echo $type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo sanitize($message); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i>
                        Username or Email
                    </label>
                    <input type="text" id="username" name="username" class="form-input" 
                           placeholder="Enter your username or email" required
                           value="<?php echo isset($_POST['username']) ? sanitize($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" value="1">
                        <span class="checkmark"></span>
                        Remember me for 30 days
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            
            <div class="login-footer">
                <p>Default credentials: <strong>admin</strong> / <strong>admin123</strong></p>
                <p><strong>Important:</strong> Change password after first login</p>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }
        
        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>