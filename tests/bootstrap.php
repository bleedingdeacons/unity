<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap.
 *
 * WordPress stand-ins, the Brain Monkey lifecycle and the Sentinel logger stub
 * all come from bleedingdeacons/wp-mocks, shared across the plugin suite. The
 * package's bootstrap loads Patchwork before anything patchable — Brain Monkey
 * only requires it inside Monkey\setUp(), by which time the stubs are defined,
 * so leaving it to Brain Monkey means any attempt to override a stub dies with
 * Patchwork\Exceptions\DefinedTooEarly.
 *
 * Anything defining WordPress functions of its own must therefore come after
 * the require below, not before it.
 */

use BleedingDeacons\WpMocks\WpState;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/bleedingdeacons/wp-mocks/bootstrap.php';

// Makes plugins_url()/plugin_dir_url() answer with Unity's own path.
WpState::$pluginSlug = 'unity';

// WordPress constants Unity's own code reads.
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
