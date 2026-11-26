-- ============================================================
-- Eventora Database Schema
-- Campus Event Booking System
-- Version: 1.0
-- Database: MySQL (InnoDB)
-- Charset: utf8mb4
-- ============================================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `eventora`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `eventora`;

-- ============================================================
-- 1. USERS TABLE
-- Stores all user accounts (students, organizers, administrators)
-- BR-01: Each account has one role
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `user_id`       INT AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(100) NOT NULL,
    `email`         VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          ENUM('student', 'organizer', 'admin') NOT NULL DEFAULT 'student',
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. ORGANIZER PROFILES TABLE
-- Extended profile for organizer accounts with approval workflow
-- FR-AUTH-004, FR-ORG-001 to FR-ORG-007
-- ============================================================
CREATE TABLE IF NOT EXISTS `organizer_profiles` (
    `profile_id`       INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`          INT NOT NULL,
    `bio`              TEXT DEFAULT NULL,
    `phone`            VARCHAR(20) DEFAULT NULL,
    `organization`     VARCHAR(255) DEFAULT NULL,
    `approval_status`  ENUM('pending', 'approved', 'rejected', 'suspended') NOT NULL DEFAULT 'pending',
    `approved_by`      INT DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,
    `decided_at`       DATETIME DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
    UNIQUE KEY `uk_organizer_user` (`user_id`),
    INDEX `idx_org_status` (`approval_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. EVENTS TABLE
-- Stores all event data with approval workflow and capacity
-- FR-EVT-001 to FR-EVT-010, BR-02, BR-06
-- ============================================================
CREATE TABLE IF NOT EXISTS `events` (
    `event_id`         INT AUTO_INCREMENT PRIMARY KEY,
    `organizer_id`     INT NOT NULL,
    `title`            VARCHAR(255) NOT NULL,
    `description`      TEXT NOT NULL,
    `category`         VARCHAR(100) NOT NULL,
    `venue`            VARCHAR(255) NOT NULL,
    `start_date`       DATETIME NOT NULL,
    `end_date`         DATETIME NOT NULL,
    `price`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `capacity`         INT NOT NULL,
    `seats_available`  INT NOT NULL,
    `status`           ENUM('draft', 'pending_approval', 'approved', 'published', 'suspended', 'cancelled', 'archived') NOT NULL DEFAULT 'draft',
    `approved_by`      INT DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,
    `approved_at`      DATETIME DEFAULT NULL,
    `waitlist_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `max_per_student`  INT NOT NULL DEFAULT 10,
    `image_url`        VARCHAR(500) DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`organizer_id`) REFERENCES `organizer_profiles`(`profile_id`) ON DELETE RESTRICT,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
    INDEX `idx_events_status` (`status`),
    INDEX `idx_events_start` (`start_date`),
    INDEX `idx_events_category` (`category`),
    INDEX `idx_events_organizer` (`organizer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. PROMO CODES TABLE
-- Discount codes with configurable rules
-- FR-PROMO-001 to FR-PROMO-006, BR-11, BR-12
-- ============================================================
CREATE TABLE IF NOT EXISTS `promo_codes` (
    `promo_id`       INT AUTO_INCREMENT PRIMARY KEY,
    `code`           VARCHAR(50) NOT NULL UNIQUE,
    `discount_type`  ENUM('percentage', 'fixed') NOT NULL,
    `discount_value` DECIMAL(10,2) NOT NULL,
    `min_spend`      DECIMAL(10,2) DEFAULT NULL,
    `max_discount`   DECIMAL(10,2) DEFAULT NULL,
    `total_use_limit` INT DEFAULT NULL,
    `per_user_limit` INT NOT NULL DEFAULT 1,
    `times_used`     INT NOT NULL DEFAULT 0,
    `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
    `valid_from`     DATETIME NOT NULL,
    `valid_until`    DATETIME NOT NULL,
    `created_by`     INT DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
    INDEX `idx_promo_code` (`code`),
    INDEX `idx_promo_active` (`is_active`, `valid_from`, `valid_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. BOOKINGS TABLE
-- Seat reservations with pricing snapshot
-- FR-BKG-001 to FR-BKG-010, BR-02, BR-04, BR-05, BR-07, BR-08, BR-20
-- ============================================================
CREATE TABLE IF NOT EXISTS `bookings` (
    `booking_id`      INT AUTO_INCREMENT PRIMARY KEY,
    `booking_ref`     VARCHAR(20) NOT NULL UNIQUE,
    `user_id`         INT NOT NULL,
    `event_id`        INT NOT NULL,
    `seats`           INT NOT NULL,
    `unit_price`      DECIMAL(10,2) NOT NULL,
    `subtotal`        DECIMAL(10,2) NOT NULL,
    `discount_type`   VARCHAR(50) DEFAULT NULL,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `promo_code_id`   INT DEFAULT NULL,
    `total`           DECIMAL(10,2) NOT NULL,
    `status`          ENUM('pending_payment', 'confirmed', 'cancelled', 'expired') NOT NULL DEFAULT 'pending_payment',
    `hold_expires_at` DATETIME DEFAULT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `cancelled_at`    DATETIME DEFAULT NULL,

    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE RESTRICT,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE RESTRICT,
    FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes`(`promo_id`) ON DELETE SET NULL,
    INDEX `idx_bookings_user` (`user_id`),
    INDEX `idx_bookings_event` (`event_id`),
    INDEX `idx_bookings_status` (`status`),
    INDEX `idx_bookings_hold` (`status`, `hold_expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. PAYMENTS TABLE
-- PayHere sandbox transaction records
-- FR-PAY-001 to FR-PAY-010, BR-07, BR-19
-- ============================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `payment_id`     INT AUTO_INCREMENT PRIMARY KEY,
    `order_ref`      VARCHAR(50) NOT NULL UNIQUE,
    `booking_id`     INT NOT NULL,
    `amount`         DECIMAL(10,2) NOT NULL,
    `currency`       VARCHAR(3) NOT NULL DEFAULT 'LKR',
    `status`         ENUM('pending', 'success', 'failed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    `payhere_tx_ref` VARCHAR(100) DEFAULT NULL,
    `response_code`  INT DEFAULT NULL,
    `hash_verified`  TINYINT(1) DEFAULT NULL,
    `paid_at`        DATETIME DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE RESTRICT,
    INDEX `idx_payments_booking` (`booking_id`),
    INDEX `idx_payments_order` (`order_ref`),
    INDEX `idx_payments_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. TICKETS TABLE
-- QR electronic tickets with secure tokens
-- FR-TKT-001 to FR-TKT-007, BR-16
-- ============================================================
CREATE TABLE IF NOT EXISTS `tickets` (
    `ticket_id`  INT AUTO_INCREMENT PRIMARY KEY,
    `token`      VARCHAR(64) NOT NULL UNIQUE,
    `booking_id` INT NOT NULL,
    `event_id`   INT NOT NULL,
    `status`     ENUM('active', 'cancelled', 'used') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE RESTRICT,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE RESTRICT,
    INDEX `idx_tickets_token` (`token`),
    INDEX `idx_tickets_booking` (`booking_id`),
    INDEX `idx_tickets_event` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. CHECK-INS TABLE
-- Venue attendance records (one check-in per ticket)
-- FR-TKT-005, FR-TKT-006, BR-16
-- ============================================================
CREATE TABLE IF NOT EXISTS `checkins` (
    `checkin_id`     INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id`      INT NOT NULL,
    `event_id`       INT NOT NULL,
    `staff_user_id`  INT NOT NULL,
    `checked_in_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`ticket_id`) ON DELETE RESTRICT,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE RESTRICT,
    FOREIGN KEY (`staff_user_id`) REFERENCES `users`(`user_id`) ON DELETE RESTRICT,
    UNIQUE KEY `uk_checkin_ticket` (`ticket_id`),
    INDEX `idx_checkins_event` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 9. WAITLIST TABLE
-- FIFO queue for sold-out events
-- FR-WAIT-001 to FR-WAIT-005, BR-13
-- ============================================================
CREATE TABLE IF NOT EXISTS `waitlist` (
    `entry_id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`          INT NOT NULL,
    `event_id`         INT NOT NULL,
    `position`         INT NOT NULL,
    `seats_requested`  INT NOT NULL DEFAULT 1,
    `status`           ENUM('waiting', 'offered', 'accepted', 'expired', 'declined', 'left') NOT NULL DEFAULT 'waiting',
    `offer_expires_at` DATETIME DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
    INDEX `idx_waitlist_user_event` (`user_id`, `event_id`, `status`),
    INDEX `idx_waitlist_event` (`event_id`, `status`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 10. WISHLIST TABLE
-- Student favourite events
-- FR-WISH-001, FR-WISH-002
-- ============================================================
CREATE TABLE IF NOT EXISTS `wishlist` (
    `wishlist_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT NOT NULL,
    `event_id`    INT NOT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_wishlist_user_event` (`user_id`, `event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 11. REVIEWS TABLE
-- Event ratings and reviews
-- FR-REV-001 to FR-REV-005, BR-17
-- ============================================================
CREATE TABLE IF NOT EXISTS `reviews` (
    `review_id`  INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT NOT NULL,
    `event_id`   INT NOT NULL,
    `rating`     TINYINT NOT NULL,
    `comment`    TEXT DEFAULT NULL,
    `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_review_user_event` (`user_id`, `event_id`),
    INDEX `idx_reviews_event` (`event_id`),
    CHECK (`rating` >= 1 AND `rating` <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 12. AUDIT LOGS TABLE
-- Tracks administrative actions and important state changes
-- FR-AUD-001, NFR-AUD-001
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `log_id`      INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT DEFAULT NULL,
    `action`      VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) DEFAULT NULL,
    `entity_id`   INT DEFAULT NULL,
    `details`     TEXT DEFAULT NULL,
    `ip_address`  VARCHAR(45) DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`),
    INDEX `idx_audit_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 13. EMAIL LOG TABLE
-- Tracks sent/failed email notifications
-- FR-MAIL-003, FR-MAIL-004, FR-MAIL-005
-- ============================================================
CREATE TABLE IF NOT EXISTS `email_logs` (
    `email_id`    INT AUTO_INCREMENT PRIMARY KEY,
    `recipient`   VARCHAR(255) NOT NULL,
    `template`    VARCHAR(100) NOT NULL,
    `subject`     VARCHAR(255) NOT NULL,
    `booking_id`  INT DEFAULT NULL,
    `event_id`    INT DEFAULT NULL,
    `status`      ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
    `attempts`    INT NOT NULL DEFAULT 0,
    `error_msg`   TEXT DEFAULT NULL,
    `sent_at`     DATETIME DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE SET NULL,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE SET NULL,
    INDEX `idx_email_status` (`status`),
    INDEX `idx_email_booking` (`booking_id`),
    INDEX `idx_email_template` (`template`, `booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
