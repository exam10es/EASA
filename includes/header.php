<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <meta name="description" content="Professional online examination system for students and educators">
    <link rel="stylesheet" href="<?php echo assetsUrl(); ?>css/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="<?php echo assetsUrl(); ?>images/favicon.ico">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span><?php echo SITE_NAME; ?></span>
                </div>
                
                <nav class="main-nav">
                    <ul>
                        <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'class="active"' : ''; ?>>Home</a></li>
                        <li><a href="index.php#majors" <?php echo strpos($_SERVER['REQUEST_URI'], '#majors') !== false ? 'class="active"' : ''; ?>>Subjects</a></li>
                        <li><a href="index.php#features" <?php echo strpos($_SERVER['REQUEST_URI'], '#features') !== false ? 'class="active"' : ''; ?>>Features</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </nav>
                
                <div class="mobile-menu-toggle">
                    <button id="mobile-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <main class="main-content">