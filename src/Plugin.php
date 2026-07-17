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
use Unity\Groups\Interfaces\GroupFactory;
use Unity\Groups\Interfaces\GroupRepository;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingChangeTracker;
use Unity\IntergroupMeetings\Interfaces\IntergroupMeetingRepository;
use Unity\Locations\Interfaces\LocationRepository;
use Unity\Meetings\Interfaces\MeetingRepository;
use Unity\Members\Interfaces\MemberChangeTracker;
use Unity\Members\Interfaces\MemberFactory;
use Unity\Members\Interfaces\MemberRepository;
use Unity\Members\Interfaces\MemberRevisor;
use Unity\Positions\Interfaces\PositionChangeTracker;
use Unity\Positions\Interfaces\PositionRepository;

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
        $this->container->get(IntergroupMeetingChangeTracker::class);

        $this->servicesInitialized = true;

        self::logDebug('Initialised', ['version' => defined('UNITY_VERSION') ? UNITY_VERSION : 'unknown']);
    }

    /**
     * Verify that all consumer-supplied repository and factory services have
     * been registered, and throw a descriptive RuntimeException at boot time
     * if any are missing.
     *
     * Unity ships as a headless service layer: the Cache and Configuration
     * bindings are registered by UnityServiceProvider, but every repository
     * and factory must be provided by the consuming site or companion plugin
     * via the `unity/loaded` hook. A misconfigured install would otherwise
     * surface as a confusing DependencyNotRegisteredException deep inside a
     * controller at the first point of use. Calling this method at the end of
     * the boot sequence (after `unity/loaded` has fired) turns that into a
     * single clear error at startup.
     *
     * @throws RuntimeException Listing every service id that is missing.
     */
    public function validateRegistrations(): void
    {
        $required = [
            MemberRepository::class,
            MemberFactory::class,
            MemberRevisor::class,
            MemberChangeTracker::class,
            GroupRepository::class,
            GroupFactory::class,
            GroupChangeTracker::class,
            MeetingRepository::class,
            LocationRepository::class,
            PositionRepository::class,
            PositionChangeTracker::class,
            IntergroupMeetingRepository::class,
            IntergroupMeetingChangeTracker::class,
        ];

        $missing = [];
        foreach ($required as $id) {
            if (!$this->container->has($id)) {
                $missing[] = $id;
            }
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'Unity: the following services have not been registered. '
                . 'Register them via the unity/loaded hook before calling validateRegistrations().' . "\n"
                . implode("\n", $missing)
            );
        }
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
     * Existing callers in unity.php can continue to call this unchanged.
     */
    public static function initContainer(): void
    {
        if (self::$instance === null) {
            self::$instance = self::create();

            register_deactivation_hook(
                dirname(__DIR__, 2) . '/unity.php',
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