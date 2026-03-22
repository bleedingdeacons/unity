<?php

declare(strict_types=1);

namespace Unity;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use RuntimeException;
use Unity\Core\DependencyContainer;
use Unity\Core\Interfaces\Container;
use Unity\Core\UnityServiceProvider;
use Unity\Groups\Interfaces\GroupChangeTracker;
use Unity\Members\Interfaces\MemberChangeTracker;
use Unity\Positions\Interfaces\PositionChangeTracker;

/**
 * Main Plugin Class
 *
 * Uses an instance-based bootstrap pattern: the container is held by a Plugin
 * instance rather than static state. A single static reference to the "default"
 * instance preserves backward compatibility with existing static callers and
 * the `unity()` helper while enabling isolated instances for testing and
 * multi-site scenarios.
 *
 * Typical production boot (unchanged for callers):
 *     Plugin::init();                    // creates default instance
 *     Plugin::getContainer()->get(…);    // works as before
 *
 * Testing / advanced usage:
 *     $plugin = Plugin::create();        // isolated instance, no global side-effects
 *     $plugin->getContainerInstance();   // private container
 */
class Plugin
{
    use \Unity\Logger\HasLogger;

    protected static function logChannel(): string
    {
        return 'unity';
    }

    // ──────────────────────────────────────────────
    //  Instance members
    // ──────────────────────────────────────────────

    private Container $container;
    private bool $servicesInitialized = false;

    /**
     * Private constructor – use Plugin::create() or the static boot methods.
     */
    private function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Create a fully isolated Plugin instance.
     *
     * The returned instance is *not* assigned as the global default unless
     * you explicitly call Plugin::setInstance(). This is the recommended
     * entry-point for unit tests and any context that needs a fresh container.
     *
     * @param Container|null $container  Supply a custom/mock container, or
     *                                   null to get a standard DependencyContainer
     *                                   pre-loaded with the default service provider.
     */
    public static function create(?Container $container = null): self
    {
        if ($container === null) {
            $container = new DependencyContainer();
            $provider  = new UnityServiceProvider();
            $provider->register($container);
        }

        return new self($container);
    }

    /**
     * Get this instance's container.
     */
    public function getContainerInstance(): Container
    {
        return $this->container;
    }

    /**
     * Eagerly resolve the core tracker services for this instance.
     *
     * @throws RuntimeException If a required service is not registered.
     */
    public function initializeServices(): void
    {
        if ($this->servicesInitialized) {
            return;
        }

        $this->container->get(GroupChangeTracker::class);
        $this->container->get(MemberChangeTracker::class);
        $this->container->get(PositionChangeTracker::class);

        $this->servicesInitialized = true;

        self::logInfo('Unity initialised', ['version' => defined('UNITY_VERSION') ? UNITY_VERSION : 'unknown']);
    }

    // ──────────────────────────────────────────────
    //  Global default instance (backward-compatible)
    // ──────────────────────────────────────────────

    private static ?self $instance = null;

    /**
     * Replace (or clear) the global default instance.
     *
     * Primarily useful in tests to inject a mock-backed Plugin and then
     * reset it in tearDown():
     *
     *     Plugin::setInstance($testPlugin);  // inject
     *     Plugin::setInstance(null);          // reset
     */
    public static function setInstance(?self $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * Get the global default instance.
     *
     * @throws RuntimeException If no default has been booted yet.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException('Plugin not initialized');
        }
        return self::$instance;
    }

    // ──────────────────────────────────────────────
    //  Static façade (preserves existing call-sites)
    // ──────────────────────────────────────────────

    /**
     * Initialize the plugin (legacy convenience method).
     *
     * Creates the default instance, registers the deactivation hook,
     * and eagerly resolves tracker services.
     */
    public static function init(): void
    {
        self::initContainer();
        self::initServices();
    }

    /**
     * Boot the default container (without resolving services yet).
     *
     * Existing callers in Unity.php can continue to call this unchanged.
     */
    public static function initContainer(): void
    {
        if (self::$instance === null) {
            self::$instance = self::create();

            register_deactivation_hook(
                dirname(__DIR__, 2) . '/Unity.php',
                [self::class, 'deactivate']
            );
        }
    }

    /**
     * Eagerly resolve the core tracker services on the default instance.
     *
     * @throws RuntimeException If initContainer() has not been called.
     */
    public static function initServices(): void
    {
        if (self::$instance === null) {
            throw new RuntimeException('Container not initialized. Call initContainer() first.');
        }

        self::$instance->initializeServices();
    }

    /**
     * Get the default container.
     *
     * @throws RuntimeException If plugin is not initialized.
     */
    public static function getContainer(): Container
    {
        return self::getInstance()->getContainerInstance();
    }

    /**
     * Deactivate the plugin.
     */
    public static function deactivate(): void
    {
        // Cleanup code here if needed
        self::$instance = null;
    }
}