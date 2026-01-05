<?php
/**
 * Examination Website - Automatic Installer
 * This script guides users through the installation process
 */

// Prevent direct access if already installed
if (file_exists('installed.lock')) {
    die('The website is already installed. Delete "installed.lock" to reinstall.');
}

// Set error reporting for installation
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize session
session_start();

// Define installation steps
$steps = [
    1 => 'Requirements Check',
    2 => 'Database Configuration',
    3 => 'Database Creation',
    4 => 'Configuration Files',
    5 => 'Sample Data',
    6 => 'Completion'
];

// Get current step
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$current_step = max(1, min(6, $current_step));

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_connection'])) {
        // Test database connection
        $host = $_POST['db_host'] ?? 'localhost';
        $name = $_POST['db_name'] ?? '';
        $user = $_POST['db_user'] ?? '';
        $pass = $_POST['db_pass'] ?? '';
        $port = $_POST['db_port'] ?? 3306;
        
        try {
            $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Check if database exists
            $stmt = $pdo->query("SHOW DATABASES LIKE '$name'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("CREATE DATABASE $name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
            
            $_SESSION['db_test_success'] = true;
            $_SESSION['db_host'] = $host;
            $_SESSION['db_name'] = $name;
            $_SESSION['db_user'] = $user;
            $_SESSION['db_pass'] = $pass;
            $_SESSION['db_port'] = $port;
        } catch (Exception $e) {
            $_SESSION['db_test_error'] = $e->getMessage();
        }
        header('Location: ?step=2');
        exit;
    }
    
    if (isset($_POST['create_database'])) {
        // Create database tables
        $host = $_SESSION['db_host'];
        $name = $_SESSION['db_name'];
        $user = $_SESSION['db_user'];
        $pass = $_SESSION['db_pass'];
        $port = $_SESSION['db_port'];
        
        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // SQL to create tables
            $sql = file_get_contents(__DIR__ . '/database_schema.sql');
            $pdo->exec($sql);
            
            // Insert default admin
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO admins (username, password, email, created_at) VALUES (?, ?, ?, NOW())")
                ->execute(['admin', $hashedPassword, 'admin@example.com']);
            
            // Insert default settings
            $pdo->prepare("INSERT INTO settings (site_name, site_description, logo_url, created_at) VALUES (?, ?, ?, NOW())")
                ->execute(['Examination Website', 'Professional online examination system', 'assets/images/logo.png']);
            
            $_SESSION['database_created'] = true;
        } catch (Exception $e) {
            $_SESSION['database_error'] = $e->getMessage();
        }
        header('Location: ?step=3');
        exit;
    }
    
    if (isset($_POST['create_config'])) {
        // Create configuration files
        $host = $_SESSION['db_host'];
        $name = $_SESSION['db_name'];
        $user = $_SESSION['db_user'];
        $pass = $_SESSION['db_pass'];
        $port = $_SESSION['db_port'];
        
        // Create database.php
        $dbConfig = "<?php\n";
        $dbConfig .= "/**\n";
        $dbConfig .= " * Database Configuration\n";
        $dbConfig .= " */\n";
        $dbConfig .= "define('DB_HOST', '$host');\n";
        $dbConfig .= "define('DB_NAME', '$name');\n";
        $dbConfig .= "define('DB_USER', '$user');\n";
        $dbConfig .= "define('DB_PASS', '$pass');\n";
        $dbConfig .= "define('DB_PORT', $port);\n";
        $dbConfig .= "define('DB_CHARSET', 'utf8mb4');\n";
        $dbConfig .= "?>";
        
        file_put_contents('config/database.php', $dbConfig);
        
        // Create constants.php
        $constants = "<?php\n";
        $constants .= "/**\n";
        $constants .= " * Site Constants\n";
        $constants .= " */\n";
        $constants .= "define('SITE_NAME', 'Examination Website');\n";
        $constants .= "define('SITE_URL', 'http://' . \$_SERVER['HTTP_HOST'] . '/');\n";
        $constants .= "define('ADMIN_URL', SITE_URL . 'admin/');\n";
        $constants .= "define('ASSETS_URL', SITE_URL . 'assets/');\n";
        $constants .= "define('UPLOADS_URL', SITE_URL . 'uploads/');\n";
        $constants .= "define('ROOT_PATH', __DIR__ . '/../');\n";
        $constants .= "define('CONFIG_PATH', ROOT_PATH . 'config/');\n";
        $constants .= "define('INCLUDES_PATH', ROOT_PATH . 'includes/');\n";
        $constants .= "define('EXAM_TIMER_ENABLED', true);\n";
        $constants .= "define('EXAM_TIMER_DURATION', 30); // minutes\n";
        $constants .= "define('SHOW_EXPLANATIONS', true);\n";
        $constants .= "define('ALLOW_RETAKES', true);\n";
        $constants .= "define('PASSING_PERCENTAGE', 70);\n";
        $constants .= "?>";
        
        file_put_contents('config/constants.php', $constants);
        
        $_SESSION['config_created'] = true;
        header('Location: ?step=4');
        exit;
    }
    
    if (isset($_POST['install_sample_data'])) {
        // Install sample data
        $host = $_SESSION['db_host'];
        $name = $_SESSION['db_name'];
        $user = $_SESSION['db_user'];
        $pass = $_SESSION['db_pass'];
        $port = $_SESSION['db_port'];
        
        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass);
            
            // Sample majors
            $majors = [
                ['Computer Science', 'Explore programming, algorithms, and computer systems'],
                ['Mathematics', 'Master calculus, algebra, and mathematical reasoning'],
                ['Biology', 'Discover life sciences, genetics, and ecosystems']
            ];
            
            foreach ($majors as $index => $major) {
                $pdo->prepare("INSERT INTO majors (name, description, image_url, created_at) VALUES (?, ?, ?, NOW())")
                    ->execute([$major[0], $major[1], 'assets/images/default-major.jpg']);
                $majorId = $pdo->lastInsertId();
                
                // Sample materials for each major
                $materials = [
                    ['Fundamentals', 'Core concepts and principles'],
                    ['Advanced Topics', 'In-depth exploration of advanced subjects']
                ];
                
                foreach ($materials as $material) {
                    $pdo->prepare("INSERT INTO materials (major_id, name, description, created_at) VALUES (?, ?, ?, NOW())")
                        ->execute([$majorId, $material[0], $material[1]]);
                    $materialId = $pdo->lastInsertId();
                    
                    // Sample chapters for each material
                    for ($chapterNum = 1; $chapterNum <= 3; $chapterNum++) {
                        $pdo->prepare("INSERT INTO chapters (material_id, chapter_number, title, description, created_at) VALUES (?, ?, ?, ?, NOW())")
                            ->execute([$materialId, $chapterNum, "Chapter $chapterNum", "Comprehensive study of chapter $chapterNum topics"]);
                        $chapterId = $pdo->lastInsertId();
                        
                        // Sample questions for each chapter
                        $questions = [
                            ['What is the primary purpose of this subject?', 'Learning', 'Research', 'Application', 'A'],
                            ['Which approach is most effective?', 'Theoretical', 'Practical', 'Combined', 'C'],
                            ['What is the key principle?', 'Understanding', 'Memorization', 'Practice', 'A'],
                            ['How do you measure success?', 'Grades', 'Understanding', 'Both', 'C'],
                            ['What is the foundation?', 'Basics', 'Advanced', 'Intermediate', 'A'],
                            ['Which skill is most important?', 'Analysis', 'Memory', 'Speed', 'A'],
                            ['What drives improvement?', 'Practice', 'Theory', 'Both', 'C'],
                            ['How do you solve problems?', 'Step by step', 'Randomly', 'Intuitively', 'A'],
                            ['What ensures retention?', 'Repetition', 'Understanding', 'Both', 'C'],
                            ['What is the ultimate goal?', 'Knowledge', 'Application', 'Both', 'C']
                        ];
                        
                        foreach ($questions as $q) {
                            $pdo->prepare("INSERT INTO questions (chapter_id, question_text, choice_a, choice_b, choice_c, correct_answer, explanation, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())")
                                ->execute([$chapterId, $q[0], $q[1], $q[2], $q[3], $q[4], 'This is the correct answer because it represents the most comprehensive understanding of the topic.']);
                        }
                    }
                }
            }
            
            $_SESSION['sample_data_installed'] = true;
        } catch (Exception $e) {
            $_SESSION['sample_data_error'] = $e->getMessage();
        }
        header('Location: ?step=5');
        exit;
    }
    
    if (isset($_POST['complete_installation'])) {
        // Create lock file and redirect to admin
        file_put_contents('installed.lock', 'Installation completed on: ' . date('Y-m-d H:i:s'));
        header('Location: admin/login.php');
        exit;
    }
}

// Clear session messages after displaying
$sessionMessages = [];
if (isset($_SESSION['db_test_success'])) {
    $sessionMessages['db_test_success'] = $_SESSION['db_test_success'];
    unset($_SESSION['db_test_success']);
}
if (isset($_SESSION['db_test_error'])) {
    $sessionMessages['db_test_error'] = $_SESSION['db_test_error'];
    unset($_SESSION['db_test_error']);
}
if (isset($_SESSION['database_created'])) {
    $sessionMessages['database_created'] = $_SESSION['database_created'];
    unset($_SESSION['database_created']);
}
if (isset($_SESSION['database_error'])) {
    $sessionMessages['database_error'] = $_SESSION['database_error'];
    unset($_SESSION['database_error']);
}
if (isset($_SESSION['config_created'])) {
    $sessionMessages['config_created'] = $_SESSION['config_created'];
    unset($_SESSION['config_created']);
}
if (isset($_SESSION['sample_data_installed'])) {
    $sessionMessages['sample_data_installed'] = $_SESSION['sample_data_installed'];
    unset($_SESSION['sample_data_installed']);
}
if (isset($_SESSION['sample_data_error'])) {
    $sessionMessages['sample_data_error'] = $_SESSION['sample_data_error'];
    unset($_SESSION['sample_data_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examination Website - Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .installer-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 800px;
            overflow: hidden;
        }
        
        .installer-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .installer-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .installer-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .progress-bar {
            height: 6px;
            background: #e5e7eb;
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
            transition: width 0.3s ease;
            width: <?php echo (($current_step - 1) / 5) * 100; ?>%;
        }
        
        .step-indicators {
            display: flex;
            justify-content: space-between;
            padding: 20px 30px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .step-indicator {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }
        
        .step-indicator::after {
            content: '';
            position: absolute;
            top: 15px;
            right: -50%;
            width: 100%;
            height: 2px;
            background: #e5e7eb;
            z-index: 1;
        }
        
        .step-indicator:last-child::after {
            display: none;
        }
        
        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }
        
        .step-indicator.completed .step-number,
        .step-indicator.active .step-number {
            background: #4f46e5;
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            margin-top: 8px;
            color: #6b7280;
            text-align: center;
            font-weight: 500;
        }
        
        .step-indicator.completed .step-label,
        .step-indicator.active .step-label {
            color: #4f46e5;
            font-weight: 600;
        }
        
        .installer-content {
            padding: 40px 30px;
        }
        
        .step-content {
            display: none;
        }
        
        .step-content.active {
            display: block;
        }
        
        .step-content h2 {
            font-size: 1.8rem;
            color: #1f2937;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .step-content p {
            color: #6b7280;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .requirements-grid {
            display: grid;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .requirement-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            background: #f9fafb;
            border-radius: 12px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .requirement-item.passed {
            border-color: #10b981;
            background: #ecfdf5;
        }
        
        .requirement-item.failed {
            border-color: #ef4444;
            background: #fef2f2;
        }
        
        .requirement-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .requirement-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .requirement-item.passed .requirement-icon {
            background: #10b981;
            color: white;
        }
        
        .requirement-item.failed .requirement-icon {
            background: #ef4444;
            color: white;
        }
        
        .requirement-text h4 {
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .requirement-text p {
            color: #6b7280;
            font-size: 14px;
            margin: 0;
        }
        
        .requirement-status {
            font-size: 24px;
            font-weight: bold;
        }
        
        .requirement-item.passed .requirement-status {
            color: #10b981;
        }
        
        .requirement-item.failed .requirement-status {
            color: #ef4444;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .message {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .message.success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        .message.info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }
        
        .database-progress {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .progress-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .progress-item:last-child {
            margin-bottom: 0;
        }
        
        .progress-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .progress-icon.pending {
            background: #e5e7eb;
            color: #6b7280;
        }
        
        .progress-icon.completed {
            background: #10b981;
            color: white;
        }
        
        .completion-card {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 2px solid #10b981;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .completion-icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
            color: white;
        }
        
        .completion-card h3 {
            color: #065f46;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .completion-card p {
            color: #047857;
            margin-bottom: 0;
        }
        
        .credentials-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .credentials-card h4 {
            color: #1f2937;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .credential-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .credential-item:last-child {
            border-bottom: none;
        }
        
        .credential-label {
            font-weight: 600;
            color: #374151;
        }
        
        .credential-value {
            font-family: monospace;
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            color: #1f2937;
        }
        
        .links-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        
        .link-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .link-card:hover {
            border-color: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .link-card h5 {
            color: #1f2937;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .link-card p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .installer-header h1 {
                font-size: 2rem;
            }
            
            .installer-content {
                padding: 30px 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .step-indicators {
                padding: 15px 20px;
            }
            
            .step-label {
                font-size: 10px;
            }
            
            .links-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>Examination Website</h1>
            <p>Professional Online Examination System Installation</p>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
        
        <div class="step-indicators">
            <?php foreach ($steps as $num => $label): ?>
                <div class="step-indicator <?php echo $num < $current_step ? 'completed' : ($num == $current_step ? 'active' : ''); ?>">
                    <div class="step-number"><?php echo $num; ?></div>
                    <div class="step-label"><?php echo $label; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="installer-content">
            <!-- Step 1: Requirements Check -->
            <div class="step-content <?php echo $current_step == 1 ? 'active' : ''; ?>">
                <h2>Server Requirements Check</h2>
                <p>Please ensure all requirements are met before proceeding with the installation.</p>
                
                <div class="requirements-grid">
                    <?php
                    $phpVersion = phpversion();
                    $phpPassed = version_compare($phpVersion, '7.4.0', '>=');
                    
                    $extensions = ['pdo', 'pdo_mysql', 'gd', 'json', 'fileinfo', 'mbstring'];
                    $extensionsStatus = [];
                    foreach ($extensions as $ext) {
                        $extensionsStatus[$ext] = extension_loaded($ext);
                    }
                    
                    $folders = ['config', 'uploads'];
                    $foldersStatus = [];
                    foreach ($folders as $folder) {
                        $foldersStatus[$folder] = is_writable($folder);
                    }
                    
                    $allPassed = $phpPassed && !in_array(false, $extensionsStatus) && !in_array(false, $foldersStatus);
                    ?>
                    
                    <div class="requirement-item <?php echo $phpPassed ? 'passed' : 'failed'; ?>">
                        <div class="requirement-info">
                            <div class="requirement-icon">🐘</div>
                            <div class="requirement-text">
                                <h4>PHP Version</h4>
                                <p>Minimum required: 7.4.0</p>
                            </div>
                        </div>
                        <div class="requirement-status"><?php echo $phpVersion; ?></div>
                    </div>
                    
                    <?php foreach ($extensions as $ext): ?>
                        <div class="requirement-item <?php echo $extensionsStatus[$ext] ? 'passed' : 'failed'; ?>">
                            <div class="requirement-info">
                                <div class="requirement-icon">🔌</div>
                                <div class="requirement-text">
                                    <h4><?php echo strtoupper($ext); ?> Extension</h4>
                                    <p>Required for database and file operations</p>
                                </div>
                            </div>
                            <div class="requirement-status"><?php echo $extensionsStatus[$ext] ? '✓' : '✗'; ?></div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($folders as $folder): ?>
                        <div class="requirement-item <?php echo $foldersStatus[$folder] ? 'passed' : 'failed'; ?>">
                            <div class="requirement-info">
                                <div class="requirement-icon">📁</div>
                                <div class="requirement-text">
                                    <h4><?php echo ucfirst($folder); ?> Folder</h4>
                                    <p>Must be writable for configuration</p>
                                </div>
                            </div>
                            <div class="requirement-status"><?php echo $foldersStatus[$folder] ? '✓' : '✗'; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="btn-group">
                    <?php if ($allPassed): ?>
                        <a href="?step=2" class="btn btn-primary">Next Step</a>
                    <?php else: ?>
                        <p style="color: #ef4444; font-weight: 600;">Please fix the failed requirements before continuing.</p>
                        <button class="btn btn-secondary" onclick="location.reload()">Refresh</button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Step 2: Database Configuration -->
            <div class="step-content <?php echo $current_step == 2 ? 'active' : ''; ?>">
                <h2>Database Configuration</h2>
                <p>Enter your database connection details. The installer will create the database if it doesn't exist.</p>
                
                <?php if (isset($sessionMessages['db_test_success'])): ?>
                    <div class="message success">
                        <span>✓</span>
                        <span>Database connection successful!</span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($sessionMessages['db_test_error'])): ?>
                    <div class="message error">
                        <span>✗</span>
                        <span>Connection failed: <?php echo htmlspecialchars($sessionMessages['db_test_error']); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Database Name</label>
                        <input type="text" id="db_name" name="db_name" placeholder="examination_db" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">Database Username</label>
                        <input type="text" id="db_user" name="db_user" placeholder="root" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">Database Password</label>
                        <input type="password" id="db_pass" name="db_pass" placeholder="Enter your database password">
                    </div>
                    
                    <div class="form-group">
                        <label for="db_port">Database Port</label>
                        <input type="number" id="db_port" name="db_port" value="3306" required>
                    </div>
                    
                    <div class="btn-group">
                        <a href="?step=1" class="btn btn-secondary">Previous</a>
                        <button type="submit" name="test_connection" class="btn btn-primary">Test Connection</button>
                        <?php if (isset($sessionMessages['db_test_success'])): ?>
                            <a href="?step=3" class="btn btn-success">Next Step</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Step 3: Database Creation -->
            <div class="step-content <?php echo $current_step == 3 ? 'active' : ''; ?>">
                <h2>Database Creation</h2>
                <p>The installer will now create all necessary database tables and insert default data.</p>
                
                <?php if (isset($sessionMessages['database_created'])): ?>
                    <div class="message success">
                        <span>✓</span>
                        <span>Database tables created successfully!</span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($sessionMessages['database_error'])): ?>
                    <div class="message error">
                        <span>✗</span>
                        <span>Error: <?php echo htmlspecialchars($sessionMessages['database_error']); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="database-progress">
                    <div class="progress-item">
                        <div class="progress-icon pending">1</div>
                        <span>Create database tables</span>
                    </div>
                    <div class="progress-item">
                        <div class="progress-icon pending">2</div>
                        <span>Create default admin account</span>
                    </div>
                    <div class="progress-item">
                        <div class="progress-icon pending">3</div>
                        <span>Insert default settings</span>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="btn-group">
                        <a href="?step=2" class="btn btn-secondary">Previous</a>
                        <button type="submit" name="create_database" class="btn btn-primary">Create Database</button>
                        <?php if (isset($sessionMessages['database_created'])): ?>
                            <a href="?step=4" class="btn btn-success">Next Step</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Step 4: Configuration Files -->
            <div class="step-content <?php echo $current_step == 4 ? 'active' : ''; ?>">
                <h2>Configuration Files</h2>
                <p>The installer will create the necessary configuration files for your website.</p>
                
                <?php if (isset($sessionMessages['config_created'])): ?>
                    <div class="message success">
                        <span>✓</span>
                        <span>Configuration files created successfully!</span>
                    </div>
                <?php endif; ?>
                
                <div class="database-progress">
                    <div class="progress-item">
                        <div class="progress-icon pending">1</div>
                        <span>Create database configuration</span>
                    </div>
                    <div class="progress-item">
                        <div class="progress-icon pending">2</div>
                        <span>Create site constants</span>
                    </div>
                    <div class="progress-item">
                        <div class="progress-icon pending">3</div>
                        <span>Set file permissions</span>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="btn-group">
                        <a href="?step=3" class="btn btn-secondary">Previous</a>
                        <button type="submit" name="create_config" class="btn btn-primary">Create Config Files</button>
                        <?php if (isset($sessionMessages['config_created'])): ?>
                            <a href="?step=5" class="btn btn-success">Next Step</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Step 5: Sample Data -->
            <div class="step-content <?php echo $current_step == 5 ? 'active' : ''; ?>">
                <h2>Sample Data (Optional)</h2>
                <p>Would you like to install sample data to help you get started? This will create sample majors, materials, chapters, and questions.</p>
                
                <?php if (isset($sessionMessages['sample_data_installed'])): ?>
                    <div class="message success">
                        <span>✓</span>
                        <span>Sample data installed successfully!</span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($sessionMessages['sample_data_error'])): ?>
                    <div class="message error">
                        <span>✗</span>
                        <span>Error: <?php echo htmlspecialchars($sessionMessages['sample_data_error']); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="database-progress">
                    <div class="progress-item">
                        <div class="progress-icon pending">1</div>
                        <span>Create 3 sample majors (Computer Science, Mathematics, Biology)</span>
                    </div>
                    <div class="progress-item">
                        <div class="progress-icon pending">2</div>
                        <span>Create 2 materials per major</span>
                    </div>
                    <div class="progress-item">
                        <div class="progress-icon pending">3</div>
                        <span>Create 3 chapters per material</span>
                    </div>
                    <div class="progress-item">
                        <div class="progress-icon pending">4</div>
                        <span>Create 10 questions per chapter</span>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="btn-group">
                        <a href="?step=4" class="btn btn-secondary">Previous</a>
                        <button type="submit" name="install_sample_data" class="btn btn-success">Install Sample Data</button>
                        <a href="?step=6" class="btn btn-primary">Skip & Continue</a>
                    </div>
                </form>
            </div>
            
            <!-- Step 6: Completion -->
            <div class="step-content <?php echo $current_step == 6 ? 'active' : ''; ?>">
                <div class="completion-card">
                    <div class="completion-icon">✓</div>
                    <h3>Installation Complete!</h3>
                    <p>Your examination website is now ready to use.</p>
                </div>
                
                <div class="credentials-card">
                    <h4>Default Admin Credentials</h4>
                    <div class="credential-item">
                        <span class="credential-label">Username:</span>
                        <span class="credential-value">admin</span>
                    </div>
                    <div class="credential-item">
                        <span class="credential-label">Password:</span>
                        <span class="credential-value">admin123</span>
                    </div>
                    <p style="margin-top: 15px; color: #ef4444; font-size: 14px;">
                        <strong>Important:</strong> Please change the admin password immediately after first login.
                    </p>
                </div>
                
                <div class="links-grid">
                    <div class="link-card">
                        <h5>Admin Panel</h5>
                        <p>Manage majors, materials, chapters, and questions</p>
                        <a href="admin/login.php" class="btn btn-primary">Go to Admin</a>
                    </div>
                    <div class="link-card">
                        <h5>Public Website</h5>
                        <p>View the student-facing examination website</p>
                        <a href="public/index.php" class="btn btn-secondary">View Website</a>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="btn-group" style="justify-content: center;">
                        <button type="submit" name="complete_installation" class="btn btn-success" style="padding: 15px 40px; font-size: 18px;">
                            Complete Installation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>