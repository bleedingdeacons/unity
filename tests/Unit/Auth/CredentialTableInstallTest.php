<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use Unity\Auth\WpdbPasswordCredentialRepository;
use wpdb;

require_once __DIR__ . '/WpdbPasswordCredentialRepositoryTest.php';

/**
 * A wpdb that can be told which tables exist, so the absorb step can be
 * driven down both of its branches.
 */
final class InstallWpdbStub extends WpdbStub
{
    /** @var array<int, string> Tables SHOW TABLES LIKE will admit to. */
    public array $existingTables = [];

    public function get_var(string $sql): mixed
    {
        $this->queries[] = $sql;

        foreach ($this->existingTables as $table) {
            // prepare() has already quoted the argument by the time this
            // sees it, which is what the repository passes through.
            if (str_contains($sql, "'" . $table . "'")) {
                return $table;
            }
        }

        return null;
    }
}

/**
 * Creating the shared table, and absorbing the two private ones it
 * replaces.
 *
 * <p>The absorb is the part worth pinning down. A member who set a
 * password in Reach and later set one in Fellowship holds two different
 * hashes, and only one of them is the password they believe they have —
 * so the copy has to be ordered by <code>updated_at</code> rather than by
 * whichever table happens to be read second.</p>
 */
final class CredentialTableInstallTest extends TestCase
{
    private InstallWpdbStub $wpdb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wpdb = new InstallWpdbStub();

        if (!function_exists('dbDelta')) {
            // Declared in the global namespace, which is where the
            // repository's `use function dbDelta;` resolves it.
            eval('function dbDelta($sql) { $GLOBALS["unity_test_dbdelta"][] = $sql; return []; }');
        }

        $GLOBALS['unity_test_dbdelta'] = [];
    }

    /**
     * @test
     */
    public function it_creates_the_shared_table(): void
    {
        /** @var wpdb $wpdb */
        $wpdb = $this->wpdb;

        WpdbPasswordCredentialRepository::install($wpdb);

        $sql = implode("\n", $GLOBALS['unity_test_dbdelta']);

        $this->assertStringContainsString('CREATE TABLE wp_unity_credentials', $sql);
        $this->assertStringContainsString('PRIMARY KEY  (email)', $sql);
        $this->assertStringContainsString('KEY reset_token_hash', $sql);
    }

    /**
     * A site with neither old table — a fresh install, or one that only
     * ever ran Fellowship — must not be a failure, and must not issue a
     * copy against a table that is not there.
     *
     * @test
     */
    public function it_copies_nothing_when_there_is_nothing_to_copy(): void
    {
        /** @var wpdb $wpdb */
        $wpdb = $this->wpdb;

        WpdbPasswordCredentialRepository::install($wpdb);

        $copies = array_filter(
            $this->wpdb->queries,
            static fn(string $q): bool => str_contains($q, 'INSERT INTO wp_unity_credentials')
        );

        $this->assertSame([], $copies);
    }

    /**
     * @test
     */
    public function it_copies_from_whichever_old_tables_exist(): void
    {
        $this->wpdb->existingTables = ['wp_reach_credentials', 'wp_fellowship_credentials'];

        /** @var wpdb $wpdb */
        $wpdb = $this->wpdb;

        WpdbPasswordCredentialRepository::install($wpdb);

        $copies = array_values(array_filter(
            $this->wpdb->queries,
            static fn(string $q): bool => str_contains($q, 'INSERT INTO wp_unity_credentials')
        ));

        $this->assertCount(2, $copies);
        $this->assertStringContainsString('FROM wp_reach_credentials', $copies[0]);
        $this->assertStringContainsString('FROM wp_fellowship_credentials', $copies[1]);
    }

    /**
     * The newer row wins, in either order, so running this twice or with
     * the tables swapped reaches the same answer.
     *
     * @test
     */
    public function the_newer_password_wins(): void
    {
        $this->wpdb->existingTables = ['wp_reach_credentials'];

        /** @var wpdb $wpdb */
        $wpdb = $this->wpdb;

        WpdbPasswordCredentialRepository::install($wpdb);

        $copies = array_values(array_filter(
            $this->wpdb->queries,
            static fn(string $q): bool => str_contains($q, 'INSERT INTO wp_unity_credentials')
        ));

        $this->assertCount(1, $copies);
        $copy = $copies[0];

        $this->assertStringContainsString('ON DUPLICATE KEY UPDATE', $copy);
        $this->assertStringContainsString(
            'password_hash = IF(VALUES(updated_at) > wp_unity_credentials.updated_at',
            $copy
        );
        $this->assertStringContainsString(
            'updated_at = GREATEST(wp_unity_credentials.updated_at, VALUES(updated_at))',
            $copy
        );
    }

    /**
     * This is the only copy of some members' passwords, and the migration
     * runs unattended on an admin page load. A bad one that has also
     * destroyed its source is not recoverable.
     *
     * @test
     */
    public function it_never_drops_the_tables_it_read(): void
    {
        $this->wpdb->existingTables = ['wp_reach_credentials', 'wp_fellowship_credentials'];

        /** @var wpdb $wpdb */
        $wpdb = $this->wpdb;

        WpdbPasswordCredentialRepository::install($wpdb);

        foreach ($this->wpdb->queries as $query) {
            $this->assertStringNotContainsStringIgnoringCase('DROP TABLE', $query);
            $this->assertStringNotContainsStringIgnoringCase('TRUNCATE', $query);
        }

        $this->assertSame([], $this->wpdb->deletes);
    }
}
