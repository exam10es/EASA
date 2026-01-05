<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Admin Panel - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo assetsUrl(); ?>css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span><?php echo SITE_NAME; ?></span>
                </div>
                <button class="sidebar-toggle" id="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="majors/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/majors/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-book-open"></i>
                            <span>Majors</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="materials/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/materials/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-layer-group"></i>
                            <span>Materials</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="chapters/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/chapters/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-list-ol"></i>
                            <span>Chapters</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="questions/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/questions/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-question-circle"></i>
                            <span>Questions</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="results/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/results/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-check"></i>
                            <span>Exam Results</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="breadcrumb">
                        <?php 
                        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                        $segments = explode('/', trim($path, '/'));
                        $breadcrumb = [];
                        
                        echo '<a href="dashboard.php">Dashboard</a>';
                        
                        for ($i = 1; $i < count($segments); $i++) {
                            if ($segments[$i] === 'admin') continue;
                            $url = implode('/', array_slice($segments, 0, $i + 1));
                            $name = ucfirst(str_replace(['.php', '_'], ['', ' '], $segments[$i]));
                            echo ' <i class="fas fa-chevron-right"></i> ';
                            if ($i === count($segments) - 1) {
                                echo '<span>' . $name . '</span>';
                            } else {
                                echo '<a href="' . $url . '">' . $name . '</a>';
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="user-menu">
                        <button class="user-menu-toggle" id="user-menu-toggle">
                            <div class="user-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <span><?php echo sanitize($admin['username']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="user-dropdown" id="user-dropdown">
                            <a href="settings.php">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                            <a href="logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Flash Messages -->
            <?php 
            $flashMessages = getFlashMessages();
            if (!empty($flashMessages)): 
            ?>
                <div class="flash-messages">
                    <?php foreach ($flashMessages as $type => $message): ?>
                        <div class="alert alert-<?php echo $type; ?>">
                            <i class="fas fa-<?php echo $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                            <?php echo sanitize($message); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Page Content -->
            <div class="page-content">