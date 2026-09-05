<?php

declare(strict_types=1);

namespace Unity;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use RuntimeException;
use Unity\Auth\WpdbPasswordCredentialRepository;
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

        // Deferred to admin_init, as Amber defers its own: this runs
        // dbDelta, which needs wp-admin's upgrade.php and the globals that
        // come with a fully booted admin request. A front-end page load
        // does not need the table to exist before it is next asked for.
        add_action('admin_init', [self::class, 'maybeRunMigrations']);

        self::logDebug('Initialised', ['version' => defined('UNITY_VERSION') ? UNITY_VERSION : 'unknown']);
    }

    /**
     * Create or update Unity's own tables when the plugin version changes.
     *
     * <b>Not an activation hook, because it would never fire.</b> Unity is
     * already active wherever Reach or Fellowship are — they declare it as
     * a requirement — and WordPress does not re-run activation hooks on an
     * update. A version-gated check on admin_init is the only thing that
     * reaches an existing install, which is every install that matters
     * here. Amber's `maybe_run_migrations` works the same way and for the
     * same reason.
     *
     * dbDelta is idempotent, and the credential absorb it performs is too,
     * so a version that runs this twice costs a query and changes nothing.
     * The option is written whatever happens: a migration that throws on
     * every admin page load would turn one broken upgrade into a site
     * nobody can use, and the failure is in the log for somebody to read.
     */
    public static function maybeRunMigrations(): void
    {
        $optionKey      = 'unity_db_version';
        $currentVersion = defined('UNITY_VERSION') ? UNITY_VERSION : '0.0.0';
        $storedVersion  = get_option($optionKey, '');

        if ($storedVersion === $currentVersion) {
            return;
        }

        try {
            global $wpdb;

            WpdbPasswordCredentialRepository::install($wpdb);

            self::logInfo('Unity schema migration complete', [
                'from' => is_string($storedVersion) && $storedVersion !== '' ? $storedVersion : '(none)',
                'to'   => $currentVersion,
            ]);
        } catch (\Throwable $e) {
            self::logError('Unity schema migration failed', [
                'error' => $e->getMessage(),
            ]);
        }

        update_option($optionKey, $currentVersion);
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
     * controller at the first point of use. unity.php calls this at the end of
     * the boot sequence, after `unity/loaded` has fired, which turns that into
     * a single clear report at startup.
     *
     * It is called *after* `unity/loaded` on purpose, and the caller catches
     * the exception rather than letting it escape. By that point the dependent
     * plugins have already loaded, so this reports a misconfiguration without
     * preventing anything — a diagnostic, not a gate. Throwing during
     * `plugins_loaded` would white-screen the site, which is a worse outcome
     * than the late failure it is meant to replace.
     *
     * @throws RuntimeException Listing every service id that is missing.
     */
    public function validateRegistrations(): void
    {
        $required = [
            MemberRepository::class,
            MemberFactory::class,
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

        // MemberRevisor is deliberately NOT in the list above. It was added to
        // Unity after tsml-for-unity had already shipped, and only newer
        // versions bind it — so requiring it here would report every site
        // running an older tsml-for-unity as misconfigured when it is not.
        // Consumers must feature-detect instead:
        //
        //     if ($container->has(MemberRevisor::class)) { … }
        //
        // Anything Unity gains in future belongs here on the same terms: a
        // binding can only be required once every released implementation
        // provides it.

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
