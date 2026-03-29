<?php

declare(strict_types=1);

/**
 * Plugin Name: Unity
 * Description: An intergroup management plugin.
 * Version: 1.10.9
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Requires Plugins: sentinel
 * Author: The Bleeding Deacons
 * Author URI: https://github.com/bleedingdeacons/unity
 * Contact: thebleedingdeacons@gmail.com
 * License: MIT (Modified)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!function_exists('get_plugin_data')) {
    if (file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
}

if (function_exists('get_plugin_data')) {
    $unity_plugin_data = get_plugin_data(__FILE__, false, false);
    define('UNITY_VERSION', $unity_plugin_data['Version']);
} else {
    define('UNITY_VERSION', '1.7.4');
}
define('UNITY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UNITY_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader (provides PSR-11 and other dependencies)
$unity_autoloader = UNITY_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($unity_autoloader)) {
    require_once $unity_autoloader;
}

// Autoloader for Unity namespace
spl_autoload_register(function ($class) {
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
});

/**
 * Get the Unity dependency container
 *
 * @return \Unity\Core\Interfaces\Container
 * @throws \RuntimeException If Unity is not initialized
 */
function unity(): \Unity\Core\Interfaces\Container {
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
         * Use this hook to register custom service implementations (e.g., MeetingFactory).
         *
         * @param \Unity\Core\Interfaces\Container $container The dependency container
         */
        do_action('unity/register_services', \Unity\Plugin::getContainer());

        if (!has_action('unity/register_services')) {
            if (is_admin()) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Unity Plugin Error:</strong> Services not registered.</p></div>';
                });
            }
            function_exists('wp_log')
                ? wp_log('unity')->error('Unity Plugin Error: Services not registered - no hooks listening to unity/register_services.')
                : error_log('Unity Plugin Error: Services not registered - no hooks listening to unity/register_services.');
        }

        // Resolve tracker services — if this fails the plugin stack is
        // inconsistent so we stop here and do NOT fire unity/loaded, which
        // prevents downstream plugins (Scrutiny, Amber, etc.) from loading.
        try {
            \Unity\Plugin::initServices();
        } catch (\Throwable $e) {
            function_exists('wp_log')
                ? wp_log('unity')->error('Unity Plugin Service Error: ' . $e->getMessage(), ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()])
                : error_log('Unity Plugin Service Error: ' . $e->getMessage());

            if (is_admin()) {
                add_action('admin_notices', function() use ($e) {
                    echo '<div class="notice notice-error is-dismissible"><p>'
                        . '<strong>Unity:</strong> Services failed to initialise — all dependent plugins have been prevented from loading. '
                        . esc_html($e->getMessage())
                        . '</p></div>';
                });
            }

            return;
        }

        /**
         * Fires after Unity is fully loaded and all services are initialized.
         * Use this hook for code that depends on Unity services being available.
         *
         * @param \Unity\Core\Interfaces\Container $container The dependency container
         */
        do_action('unity/loaded', \Unity\Plugin::getContainer());

    } catch (\Exception $e) {
        function_exists('wp_log')
            ? wp_log('unity')->error('Unity Plugin Initialization Error: ' . $e->getMessage(), ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()])
            : error_log('Unity Plugin Initialization Error: ' . $e->getMessage());

        if (is_admin()) {
            add_action('admin_notices', function() use ($e) {
                $message = sprintf(
                    '<strong>Unity Plugin Error:</strong> %s',
                    esc_html($e->getMessage())
                );
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
            });
        }

        return;

    } catch (\Throwable $e) {
        function_exists('wp_log')
            ? wp_log('unity')->critical('Unity Plugin Fatal Error: ' . $e->getMessage(), ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()])
            : error_log('Unity Plugin Fatal Error: ' . $e->getMessage());

        if (is_admin()) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p><strong>Unity Plugin Fatal Error:</strong> Plugin failed to load. Check error logs.</p></div>';
            });
        }

        return;
    }
}, 10);