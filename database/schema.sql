-- ============================================================
--  ECOMUNDO Academic Forum  ·  MySQL / MariaDB (utf8mb4)
--  PRODUCTION / FRESH SCHEMA
--
--  HOW TO IMPORT (InfinityFree or any shared host):
--    1. Create the database in the control panel (e.g. my_forum).
--    2. Open phpMyAdmin, SELECT that database.
--    3. Import this file (Import tab). No CREATE DATABASE here,
--       so it works even when the user has rights over one DB only.
--
--  This file is idempotent: it drops and recreates the 7 tables.
--  Tables are created WITHOUT foreign keys and all constraints are
--  added at the end with ALTER TABLE, so the import works even when
--  phpMyAdmin keeps "Enable foreign key checks" enabled and with
--  circular references (salones <-> users).
--  Seed data: settings, 5 example classrooms, the administrator
--  (admin@ecomundo.edu.ec / Admin@2026) and, as an OPTIONAL sample,
--  one active forum assigned to all classrooms.
--  Change the admin password after the first sign-in.
-- ============================================================

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `security_logs`;
DROP TABLE IF EXISTS `responses`;
DROP TABLE IF EXISTS `forum_salones`;
DROP TABLE IF EXISTS `forums`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `salones`;
DROP TABLE IF EXISTS `settings`;

-- ------------------------------------------------------------
--  settings · global key–value configuration
-- ------------------------------------------------------------
CREATE TABLE `settings` (
    `setting_key`   VARCHAR(50)  NOT NULL,
    `setting_value` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  salones · classrooms (owned by a teacher when assigned)
-- ------------------------------------------------------------
CREATE TABLE `salones` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(80)  NOT NULL,
    `teacher_id` INT UNSIGNED NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_salones_name` (`name`),
    KEY `idx_salones_teacher` (`teacher_id`)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  users · students, teachers, guests and the administrator
-- ------------------------------------------------------------
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
    KEY `idx_users_salon` (`salon_id`)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  forums · activities with a participation time window
-- ------------------------------------------------------------
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
    KEY `idx_forums_user` (`created_by`)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  forum_salones · assignment: which classrooms see each forum
-- ------------------------------------------------------------
CREATE TABLE `forum_salones` (
    `forum_id` INT UNSIGNED NOT NULL,
    `salon_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`forum_id`, `salon_id`)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  responses · teacher (unique) / partner (reply) / conclusion (unique)
-- ------------------------------------------------------------
CREATE TABLE `responses` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `forum_id`   INT UNSIGNED NOT NULL,
    `user_id`    INT UNSIGNED NOT NULL,
    `parent_id`  INT UNSIGNED NULL,
    `type`       ENUM('teacher','partner','conclusion') NOT NULL,
    `content`    TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_responses_user_forum_type` (`user_id`, `forum_id`, `type`)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  security_logs · audit trail
-- ------------------------------------------------------------
CREATE TABLE `security_logs` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NULL,
    `event`      VARCHAR(60)  NOT NULL,
    `detail`     VARCHAR(255) NULL,
    `ip`         VARCHAR(45)  NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_security_logs_event` (`event`)
) ENGINE=InnoDB;

-- ============================================================
--  FOREIGN KEYS (added at the end: every parent table exists
--  and all inserted rows already satisfy the relationships)
-- ============================================================
ALTER TABLE `salones`
    ADD CONSTRAINT `fk_salones_teacher`
        FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `users`
    ADD CONSTRAINT `fk_users_salon`
        FOREIGN KEY (`salon_id`) REFERENCES `salones` (`id`) ON DELETE SET NULL;

ALTER TABLE `forums`
    ADD CONSTRAINT `fk_forums_user`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `forum_salones`
    ADD CONSTRAINT `fk_forum_salones_forum`
        FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE CASCADE;
ALTER TABLE `forum_salones`
    ADD CONSTRAINT `fk_forum_salones_salon`
        FOREIGN KEY (`salon_id`) REFERENCES `salones` (`id`) ON DELETE CASCADE;

ALTER TABLE `responses`
    ADD CONSTRAINT `fk_responses_forum`
        FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE CASCADE;
ALTER TABLE `responses`
    ADD CONSTRAINT `fk_responses_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `responses`
    ADD CONSTRAINT `fk_responses_parent`
        FOREIGN KEY (`parent_id`) REFERENCES `responses` (`id`) ON DELETE CASCADE;

ALTER TABLE `security_logs`
    ADD CONSTRAINT `fk_security_logs_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ============================================================
--  INITIAL DATA
-- ============================================================

-- Registration domains: accept any domain? no. Allowed: ecomundo.edu.ec
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('allow_any_domain', '0'),
('accepted_domains', 'ecomundo.edu.ec');

-- Example classrooms (used by the admin to assign owners)
INSERT INTO `salones` (`name`) VALUES
('9th "A"'), ('9th "B"'), ('9th "C"'), ('9th "D"'), ('9th "E"');

-- Administrator (bcrypt of "Admin@2026"); change it after first sign-in
INSERT INTO `users` (`email`, `first_name`, `last_name`, `password`, `role`)
VALUES (
    'admin@ecomundo.edu.ec',
    'Administrator',
    'ECOMUNDO',
    '$2y$10$vwY8oBRVgjJZO0rgS6ipaOWnwOqodqHC6oWPJEBEwfPtQx9Wh8.Im',
    'admin'
);

-- ------------------------------------------------------------------
--  OPTIONAL demo forum (comment out for a completely clean start).
--  A real activity is created by the teacher from Admin -> Forum
--  Management with its own title, question and time window.
-- ------------------------------------------------------------------
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

INSERT INTO `forum_salones` (`forum_id`, `salon_id`)
SELECT 1, `id` FROM `salones`;