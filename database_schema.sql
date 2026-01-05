-- Examination Website Database Schema
-- This file creates all necessary tables for the examination system

SET FOREIGN_KEY_CHECKS = 0;

-- Admins table for administrator accounts
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` DATETIME NULL DEFAULT NULL,
    `failed_login_attempts` INT(11) NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Majors table (subjects/categories)
CREATE TABLE IF NOT EXISTS `majors` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `image_url` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    INDEX `idx_name` (`name`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Materials table (courses/modules within majors)
CREATE TABLE IF NOT EXISTS `materials` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `major_id` INT(11) UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`major_id`) REFERENCES `majors`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_major_id` (`major_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chapters table (sections within materials)
CREATE TABLE IF NOT EXISTS `chapters` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `material_id` INT(11) UNSIGNED NOT NULL,
    `chapter_number` INT(11) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`material_id`) REFERENCES `materials`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_material_id` (`material_id`),
    INDEX `idx_chapter_number` (`chapter_number`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Questions table (exam questions)
CREATE TABLE IF NOT EXISTS `questions` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `chapter_id` INT(11) UNSIGNED NOT NULL,
    `question_text` TEXT NOT NULL,
    `choice_a` VARCHAR(500) NOT NULL,
    `choice_b` VARCHAR(500) NOT NULL,
    `choice_c` VARCHAR(500) NOT NULL,
    `correct_answer` ENUM('A', 'B', 'C') NOT NULL,
    `explanation` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`chapter_id`) REFERENCES `chapters`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_chapter_id` (`chapter_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exam results table
CREATE TABLE IF NOT EXISTS `exam_results` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_name` VARCHAR(100) NOT NULL,
    `chapter_id` INT(11) UNSIGNED NOT NULL,
    `score` INT(11) NOT NULL,
    `total_questions` INT(11) NOT NULL,
    `percentage` DECIMAL(5,2) NOT NULL,
    `correct_answers` INT(11) NOT NULL,
    `wrong_answers` INT(11) NOT NULL,
    `completed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `time_taken_seconds` INT(11) NULL DEFAULT NULL,
    `answers_data` JSON NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`chapter_id`) REFERENCES `chapters`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_chapter_id` (`chapter_id`),
    INDEX `idx_student_name` (`student_name`),
    INDEX `idx_completed_at` (`completed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table for site configuration
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `site_name` VARCHAR(100) NOT NULL,
    `site_description` TEXT NULL DEFAULT NULL,
    `logo_url` VARCHAR(255) NULL DEFAULT NULL,
    `contact_email` VARCHAR(100) NULL DEFAULT NULL,
    `admin_email` VARCHAR(100) NULL DEFAULT NULL,
    `exam_timer_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `exam_timer_duration` INT(11) NOT NULL DEFAULT 30,
    `show_explanations` TINYINT(1) NOT NULL DEFAULT 1,
    `allow_retakes` TINYINT(1) NOT NULL DEFAULT 1,
    `passing_percentage` INT(11) NOT NULL DEFAULT 70,
    `maintenance_mode` TINYINT(1) NOT NULL DEFAULT 0,
    `enable_registration` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CSRF tokens table for security
CREATE TABLE IF NOT EXISTS `csrf_tokens` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL,
    `session_id` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_token` (`token`),
    INDEX `idx_session_id` (`session_id`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log for admin actions
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id` INT(11) UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(50) NULL DEFAULT NULL,
    `record_id` INT(11) NULL DEFAULT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    `user_agent` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;