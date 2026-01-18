<?php

declare(strict_types=1);

/**
 * Plugin Name: Unity
 * Description: An intergroup management plugin.
 * Version: 1.2.2
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: The Bleeding Deacons
 * Author URI: thebleedingdeacons@gmail.com
 * License: MIT (Modified)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!function_exists('get_plugin_data')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}
$confur_plugin_data = get_plugin_data(__FILE__, false, false);
define('UNITY_VERSION', $confur_plugin_data['Version']);
define('UNITY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UNITY_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader for Unity namespace
spl_autoload_register(function ($class) {
    try {
        $prefix = 'Unity\\';
        $base_dir = UNITY_PLUGIN_DIR . 'src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    } catch (\Exception $e) {
        error_log('Unity Autoloader Error: ' . $e->getMessage());
    } catch (\Throwable $e) {
        error_log('Unity Autoloader Fatal Error: ' . $e->getMessage());
    }
});

/**
 * Get the Unity dependency container
 *
 * @return \Unity\Core\DependencyContainer
 * @throws \RuntimeException If Unity is not initialized
 */
function unity(): \Unity\Core\DependencyContainer {
    return \Unity\Plugin::getContainer();
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    try {
        if (!class_exists('Unity\Plugin')) {
            throw new \Exception('Unity\Plugin class not found. Check that Plugin.php exists in the src/ directory.');
        }

        \Unity\Plugin::initContainer();

        /**
         * Fires after Unity's container is created but before services are resolved.
         * Use this hook to register custom service implementations (e.g., MeetingFactoryInterface).
         *
         * @param \Unity\Core\DependencyContainer $container The dependency container
         */
        do_action('unity_register_services', \Unity\Plugin::getContainer());

        \Unity\Plugin::initServices();

        /**
         * Fires after Unity is fully loaded and all services are initialized.
         * Use this hook for code that depends on Unity services being available.
         *
         * @param \Unity\Core\DependencyContainer $container The dependency container
         */
        do_action('unity_loaded', \Unity\Plugin::getContainer());

    } catch (\Exception $e) {
        error_log('Unity Plugin Initialization Error: ' . $e->getMessage());
        error_log('Unity Plugin Stack Trace: ' . $e->getTraceAsString());

        if (is_admin()) {
            add_action('admin_notices', function() use ($e) {
                $message = sprintf(
                    '<strong>Unity Plugin Error:</strong> %s',
                    esc_html($e->getMessage())
                );
                echo '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>';
            });
        }

        return;

    } catch (\Throwable $e) {
        error_log('Unity Plugin Fatal Error: ' . $e->getMessage());
        error_log('Unity Plugin Stack Trace: ' . $e->getTraceAsString());

        if (is_admin()) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p><strong>Unity Plugin Fatal Error:</strong> Plugin failed to load. Check error logs.</p></div>';
            });
        }

        return;
    }
}, 10);