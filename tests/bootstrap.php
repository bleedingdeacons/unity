<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap File
 *
 * Sets up the testing environment with WP_Mock for WordPress function mocking.
 */

// Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Initialize WP_Mock
WP_Mock::bootstrap();

// Define WordPress constants if not already defined
if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/');
}

if (!defined('UNITY_PLUGIN_DIR')) {
    define('UNITY_PLUGIN_DIR', dirname(__DIR__) . '/');
}

if (!defined('UNITY_PLUGIN_URL')) {
    define('UNITY_PLUGIN_URL', 'http://example.com/wp-content/plugins/unity/');
}

if (!defined('UNITY_VERSION')) {
    define('UNITY_VERSION', '1.0.1');
}
