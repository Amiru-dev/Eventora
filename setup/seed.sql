-- ============================================================
-- Eventora Seed Data
-- Initial administrator, test accounts and sample events
-- ============================================================

USE `eventora`;

-- ============================================================
-- 1. SEED ADMINISTRATOR ACCOUNT
-- FR-AUTH-006: Admin accounts are seeded, not self-registered
-- Password: Admin@123 (hashed with PHP password_hash)
-- ============================================================
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `is_active`) VALUES
('System Administrator', 'admin@eventora.com', '$2y$10$9Ros91R8bSaWVSay3BJfre0nq.dBaKcQk9JP8MFyqgae57oyg6Kai', 'admin', 1);

-- ============================================================
-- 2. SEED TEST STUDENT ACCOUNT
-- Password: Student@123
-- ============================================================
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `is_active`) VALUES
('Test Student', 'student@eventora.com', '$2y$10$csFV3ujJljTjozCkqJ4u/O6VnGYjq4YNmrKCzYXuFET3TYrIxgrxK', 'student', 1);

-- ============================================================
-- 3. SEED TEST ORGANIZER ACCOUNT (Pre-approved)
-- Password: Organizer@123
-- ============================================================
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `is_active`) VALUES
('Test Organizer', 'organizer@eventora.com', '$2y$10$ZU2yGdYkEqqjFs/6L4x3tu0K0PjgwN9VMHTS3ikBgNVxSmtZxL1Uq', 'organizer', 1);

-- Create approved organizer profile
INSERT INTO `organizer_profiles` (`user_id`, `bio`, `phone`, `organization`, `approval_status`, `approved_by`, `decided_at`) VALUES
(3, 'Official university club event organizer.', '+94771234567', 'NIIBS Tech Club', 'approved', 1, NOW());

-- ============================================================
-- 4. SAMPLE EVENT CATEGORIES (via sample events)
-- Categories: Workshop, Seminar, Social, Sports, Cultural, Tech
-- ============================================================

-- Sample events (all published and upcoming)
INSERT INTO `events` (`organizer_id`, `title`, `description`, `category`, `venue`, `start_date`, `end_date`, `price`, `capacity`, `seats_available`, `status`, `approved_by`, `approved_at`, `waitlist_enabled`, `max_per_student`) VALUES
(1, 'Introduction to Web Development', 'Learn the basics of HTML, CSS and JavaScript in this hands-on workshop. Suitable for beginners with no prior coding experience.', 'Workshop', 'Lab 201, IT Building', DATE_ADD(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 7 DAY) + INTERVAL 3 HOUR, 500.00, 40, 40, 'published', 1, NOW(), 1, 5),
(1, 'AI and Machine Learning Seminar', 'An introductory seminar on artificial intelligence and machine learning concepts, featuring guest speakers from the industry.', 'Seminar', 'Main Auditorium', DATE_ADD(NOW(), INTERVAL 14 DAY), DATE_ADD(NOW(), INTERVAL 14 DAY) + INTERVAL 2 HOUR, 0.00, 200, 200, 'published', 1, NOW(), 0, 10),
(1, 'Inter-Faculty Cricket Tournament', 'Annual inter-faculty cricket tournament. Register your team and compete for the championship trophy.', 'Sports', 'University Cricket Ground', DATE_ADD(NOW(), INTERVAL 21 DAY), DATE_ADD(NOW(), INTERVAL 23 DAY), 300.00, 100, 100, 'published', 1, NOW(), 1, 10),
(1, 'Cultural Night 2025', 'Celebrate diversity with performances, food stalls and cultural exhibitions from students across all faculties.', 'Cultural', 'University Grounds', DATE_ADD(NOW(), INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY) + INTERVAL 5 HOUR, 200.00, 500, 500, 'published', 1, NOW(), 1, 10),
(1, 'Hackathon: Code for Change', 'A 24-hour hackathon where teams build solutions for real-world community problems. Prizes for top 3 teams.', 'Tech', 'Innovation Hub', DATE_ADD(NOW(), INTERVAL 10 DAY), DATE_ADD(NOW(), INTERVAL 11 DAY), 1000.00, 60, 60, 'published', 1, NOW(), 1, 4),
(1, 'Photography Workshop', 'Master the art of photography with professional photographer guidance. Bring your own camera or smartphone.', 'Workshop', 'Room 305, Arts Building', DATE_ADD(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 5 DAY) + INTERVAL 4 HOUR, 750.00, 25, 25, 'published', 1, NOW(), 0, 2);

-- ============================================================
-- 5. SAMPLE PROMO CODE
-- ============================================================
INSERT INTO `promo_codes` (`code`, `discount_type`, `discount_value`, `min_spend`, `max_discount`, `total_use_limit`, `per_user_limit`, `is_active`, `valid_from`, `valid_until`, `created_by`) VALUES
('WELCOME10', 'percentage', 10.00, 500.00, 200.00, 100, 1, 1, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 1),
('FLAT100', 'fixed', 100.00, 300.00, NULL, 50, 1, 1, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY), 1);
