<?php
/**
 * Eventora - Logout Handler
 * FR-AUTH-005: Destroys session on logout
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

logout_user();

set_flash_message('info', 'You have been signed out successfully.');
redirect(APP_URL . '/pages/auth/login.php');
