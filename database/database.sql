-- NovaServe AI Intelligent Customer Support Platform
-- Database Schema Definition (MySQL 8+ / MariaDB)
-- Character Set: utf8mb4 / Collation: utf8mb4_unicode_ci

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Roles Table
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_key` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users Table
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(40) NULL,
    `avatar` VARCHAR(255) NULL,
    `status` ENUM('active', 'suspended', 'pending') NOT NULL DEFAULT 'active',
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_users_role` (`role_id`),
    KEY `idx_users_email` (`email`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Categories Table (for FAQ & Knowledge Base)
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL UNIQUE,
    `icon` VARCHAR(60) NOT NULL DEFAULT 'fa-folder',
    `description` VARCHAR(255) NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. FAQ Items Table
DROP TABLE IF EXISTS `faq_items`;
CREATE TABLE `faq_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `question` VARCHAR(300) NOT NULL,
    `answer` TEXT NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_faq_category` (`category_id`),
    KEY `idx_faq_status` (`is_active`),
    FULLTEXT KEY `ft_faq_search` (`question`, `answer`),
    CONSTRAINT `fk_faq_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Knowledge Articles Table
DROP TABLE IF EXISTS `knowledge_articles`;
CREATE TABLE `knowledge_articles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `summary` VARCHAR(500) NOT NULL,
    `content` MEDIUMTEXT NOT NULL,
    `tags` VARCHAR(255) NULL,
    `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'published',
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_article_category` (`category_id`),
    KEY `idx_article_status` (`status`),
    FULLTEXT KEY `ft_article_search` (`title`, `summary`, `content`, `tags`),
    CONSTRAINT `fk_article_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Conversations Table
DROP TABLE IF EXISTS `conversations`;
CREATE TABLE `conversations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT UNSIGNED NULL,
    `session_token` VARCHAR(100) NOT NULL,
    `status` ENUM('active', 'escalated', 'human_taken', 'resolved', 'closed') NOT NULL DEFAULT 'active',
    `channel` VARCHAR(50) NOT NULL DEFAULT 'web_chat',
    `language` VARCHAR(10) NOT NULL DEFAULT 'en',
    `primary_intent` VARCHAR(80) NULL DEFAULT 'General Question',
    `overall_sentiment` ENUM('Positive', 'Neutral', 'Negative', 'Angry') NOT NULL DEFAULT 'Neutral',
    `confidence_score` DECIMAL(5,2) NOT NULL DEFAULT 90.00,
    `assigned_staff_id` INT UNSIGNED NULL,
    `escalation_reason` VARCHAR(255) NULL,
    `provider_used` VARCHAR(50) NOT NULL DEFAULT 'Primary AI',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_activity_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_conv_customer` (`customer_id`),
    KEY `idx_conv_session` (`session_token`),
    KEY `idx_conv_status` (`status`),
    KEY `idx_conv_staff` (`assigned_staff_id`),
    CONSTRAINT `fk_conv_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_conv_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Conversation Messages Table
DROP TABLE IF EXISTS `conversation_messages`;
CREATE TABLE `conversation_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT UNSIGNED NOT NULL,
    `sender_type` ENUM('customer', 'ai', 'staff', 'system') NOT NULL,
    `sender_id` INT UNSIGNED NULL,
    `message_text` TEXT NOT NULL,
    `sources_json` TEXT NULL,
    `intent` VARCHAR(80) NULL,
    `sentiment` ENUM('Positive', 'Neutral', 'Negative', 'Angry') NULL,
    `confidence_score` DECIMAL(5,2) NULL,
    `is_voice_input` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_msg_conv` (`conversation_id`),
    CONSTRAINT `fk_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Conversation Feedback Table
DROP TABLE IF EXISTS `conversation_feedback`;
CREATE TABLE `conversation_feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT UNSIGNED NOT NULL,
    `message_id` INT UNSIGNED NOT NULL,
    `customer_id` INT UNSIGNED NULL,
    `rating` ENUM('positive', 'negative') NOT NULL,
    `comment` VARCHAR(500) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_fb_conv` (`conversation_id`),
    KEY `idx_fb_msg` (`message_id`),
    CONSTRAINT `fk_fb_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fb_msg` FOREIGN KEY (`message_id`) REFERENCES `conversation_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Support Tickets Table
DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE `support_tickets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_number` VARCHAR(50) NOT NULL UNIQUE,
    `customer_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `assigned_staff_id` INT UNSIGNED NULL,
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    `status` ENUM('open', 'pending', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_ticket_customer` (`customer_id`),
    KEY `idx_ticket_category` (`category_id`),
    KEY `idx_ticket_staff` (`assigned_staff_id`),
    KEY `idx_ticket_status` (`status`),
    KEY `idx_ticket_priority` (`priority`),
    CONSTRAINT `fk_ticket_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ticket_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_ticket_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Ticket Messages Table
DROP TABLE IF EXISTS `ticket_messages`;
CREATE TABLE `ticket_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT UNSIGNED NOT NULL,
    `sender_id` INT UNSIGNED NOT NULL,
    `message_text` TEXT NOT NULL,
    `is_internal_note` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_tm_ticket` (`ticket_id`),
    KEY `idx_tm_sender` (`sender_id`),
    CONSTRAINT `fk_tm_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tm_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Ticket Attachments Table
DROP TABLE IF EXISTS `ticket_attachments`;
CREATE TABLE `ticket_attachments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT UNSIGNED NOT NULL,
    `message_id` INT UNSIGNED NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `stored_filename` VARCHAR(255) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `uploaded_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_att_ticket` (`ticket_id`),
    KEY `idx_att_msg` (`message_id`),
    CONSTRAINT `fk_att_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_att_msg` FOREIGN KEY (`message_id`) REFERENCES `ticket_messages` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_att_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. AI Settings Table
DROP TABLE IF EXISTS `ai_settings`;
CREATE TABLE `ai_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(80) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `description` VARCHAR(255) NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. System Settings Table
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `config_key` VARCHAR(80) NOT NULL UNIQUE,
    `config_value` TEXT NULL,
    `description` VARCHAR(255) NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Notifications Table
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `role_target` VARCHAR(50) NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` VARCHAR(500) NOT NULL,
    `type` ENUM('escalation', 'angry_customer', 'new_ticket', 'ticket_reply', 'system_alert') NOT NULL,
    `reference_id` INT UNSIGNED NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_notif_user` (`user_id`),
    KEY `idx_notif_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Activity Logs Table
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` VARCHAR(500) NOT NULL,
    `ip_address` VARCHAR(50) NOT NULL DEFAULT '127.0.0.1',
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_log_user` (`user_id`),
    KEY `idx_log_action` (`action`),
    KEY `idx_log_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
