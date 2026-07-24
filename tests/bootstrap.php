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

// Minimal stand-in for the Sentinel_Log_Channel class the shared logger
// mu-plugin provides in production. HasLogger caches a channel typed against
// this class, so tests that exercise the logging path need it on the
// classpath. The stub records the level of each call so a test can assert the
// trait forwarded correctly; it is a no-op otherwise.
if (!class_exists('Sentinel_Log_Channel')) {
    class Sentinel_Log_Channel
    {
        /** @var array<int, string> */
        public array $calls = [];

        public function emergency(string $message, array $context = []): void
        {
            $this->calls[] = 'emergency';
        }
        public function alert(string $message, array $context = []): void
        {
            $this->calls[] = 'alert';
        }
        public function critical(string $message, array $context = []): void
        {
            $this->calls[] = 'critical';
        }
        public function error(string $message, array $context = []): void
        {
            $this->calls[] = 'error';
        }
        public function warning(string $message, array $context = []): void
        {
            $this->calls[] = 'warning';
        }
        public function notice(string $message, array $context = []): void
        {
            $this->calls[] = 'notice';
        }
        public function info(string $message, array $context = []): void
        {
            $this->calls[] = 'info';
        }
        public function debug(string $message, array $context = []): void
        {
            $this->calls[] = 'debug';
        }
    }
}
