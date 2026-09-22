-- ============================================================
--  ECOMUNDO Academic Forum  ·  MySQL / MySQLi
--  Import this file in phpMyAdmin (Import tab).
--  Initial admin user: admin@ecomundo.edu.ec
--  Password: Admin@2026  (change it after first sign-in)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `my_forum` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `my_forum`;

DROP TABLE IF EXISTS `security_logs`;
DROP TABLE IF EXISTS `responses`;
DROP TABLE IF EXISTS `forum_salones`;
DROP TABLE IF EXISTS `forums`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `salones`;
DROP TABLE IF EXISTS `settings`;

-- Global configuration (key-value)
CREATE TABLE `settings` (
    `setting_key`   VARCHAR(50)  NOT NULL,
    `setting_value` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB;

-- Classrooms managed by the administrator / teacher (9th "A", 9th "B", ...)
CREATE TABLE `salones` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(80)  NOT NULL,
    `teacher_id` INT UNSIGNED NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_salones_name` (`name`),
    KEY `fk_salones_teacher` (`teacher_id`),
    CONSTRAINT `fk_salones_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Users (students, teachers, guests and the administrator)
CREATE TABLE `users` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`           VARCHAR(190) NOT NULL,
    `first_name`      VARCHAR(60)  NOT NULL,
    `last_name`       VARCHAR(60)  NOT NULL,
    `salon_id`        INT UNSIGNED NULL,
    `password`        VARCHAR(255) NOT NULL,
    `role`            ENUM('student','teacher','admin','guest') NOT NULL DEFAULT 'student',
    `failed_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `locked`          TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `fk_users_salon` (`salon_id`),
    CONSTRAINT `fk_users_salon` FOREIGN KEY (`salon_id`) REFERENCES `salones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Forums with a time window (open_at / close_at)
CREATE TABLE `forums` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`      VARCHAR(190) NOT NULL,
    `subject`    VARCHAR(190) NOT NULL,
    `question`   TEXT         NOT NULL,
    `open_at`    DATETIME     NOT NULL,
    `close_at`   DATETIME     NOT NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 0,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_forums_user` (`created_by`),
    CONSTRAINT `fk_forums_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Assignment: which classrooms can access each forum (forum <-> classroom)
CREATE TABLE `forum_salones` (
    `forum_id` INT UNSIGNED NOT NULL,
    `salon_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`forum_id`, `salon_id`),
    KEY `fk_forum_salones_forum` (`forum_id`),
    KEY `fk_forum_salones_salon` (`salon_id`),
    CONSTRAINT `fk_forum_salones_forum` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_forum_salones_salon` FOREIGN KEY (`salon_id`) REFERENCES `salones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Participations: teacher (to the teacher, unique) / partner (reply) / conclusion (unique)
CREATE TABLE `responses` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `forum_id`   INT UNSIGNED NOT NULL,
    `user_id`    INT UNSIGNED NOT NULL,
    `parent_id`  INT UNSIGNED NULL,
    `type`       ENUM('teacher','partner','conclusion') NOT NULL,
    `content`    TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_responses_forum` (`forum_id`),
    KEY `fk_responses_user` (`user_id`),
    KEY `fk_responses_parent` (`parent_id`),
    KEY `idx_responses_user_forum_type` (`user_id`, `forum_id`, `type`),
    CONSTRAINT `fk_responses_forum`  FOREIGN KEY (`forum_id`)  REFERENCES `forums` (`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_responses_user`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_responses_parent` FOREIGN KEY (`parent_id`) REFERENCES `responses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Security audit (copy/screenshot/hack attempts, IP, date, etc.)
CREATE TABLE `security_logs` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NULL,
    `event`      VARCHAR(60)  NOT NULL,
    `detail`     VARCHAR(255) NULL,
    `ip`         VARCHAR(45)  NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_security_logs_user` (`user_id`),
    KEY `idx_security_logs_event` (`event`),
    CONSTRAINT `fk_security_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
--  INITIAL DATA
-- ============================================================
-- Global settings: accepted email domains for registration.
-- 'allow_any_domain' = 1 accepts any domain (gmail.com, outlook.com, ...).
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('allow_any_domain', '0'),
('accepted_domains', 'ecomundo.edu.ec');

INSERT INTO `salones` (`name`) VALUES ('9th "A"'), ('9th "B"'), ('9th "C"'), ('9th "D"'), ('9th "E"');

-- Default administrator: admin@ecomundo.edu.ec / Admin@2026
INSERT INTO `users` (`email`, `first_name`, `last_name`, `password`, `role`)
VALUES (
    'admin@ecomundo.edu.ec',
    'Administrator',
    'ECOMUNDO',
    '$2y$10$vwY8oBRVgjJZO0rgS6ipaOWnwOqodqHC6oWPJEBEwfPtQx9Wh8.Im', -- Admin@2026
    'admin'
);

-- Example active forum (adjust the dates to your schedule)
INSERT INTO `forums` (`title`, `subject`, `question`, `open_at`, `close_at`, `is_active`, `created_by`)
VALUES (
    'Forum: PISA Results and Artificial Intelligence',
    'Sociology of Education',
    'To which factors do you attribute the overall trend of the results in the latest PISA tests at a global and regional level? What connection or impact do you consider the accelerated rise of Artificial Intelligence (AI) has on these educational results and on the development of students\' critical thinking?',
    NOW(),
    DATE_ADD(NOW(), INTERVAL 7 DAY),
    1,
    1
);

-- The example forum is assigned to every classroom so it is visible to all students
INSERT INTO `forum_salones` (`forum_id`, `salon_id`)
SELECT 1, `id` FROM `salones`;

SET FOREIGN_KEY_CHECKS = 1;