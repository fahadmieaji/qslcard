<?php
// config/config.php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'test_s21af');
define('DB_USER', 'test_s21af');
define('DB_PASS', '123456');

// Project configuration
define('ROOT_PATH', dirname(__DIR__));
define('ROOT_URL', '/s21af'); // IMPORTANT: Change this if your project is in a different subfolder of htdocs or in the root.

// Security configuration
// IMPORTANT: Change this to a new random key for your installation. You can generate one at https://random.org/bytes/
define('ENCRYPTION_KEY', 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2');

// Installation configuration
define('INSTALL_LOCK_FILE', ROOT_PATH . '/install.lock');



