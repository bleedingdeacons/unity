<?php

declare(strict_types=1);

namespace Unity\Auth;

if (!defined('ABSPATH')) {
    exit;
}

use LogicException;
use Unity\Auth\Interfaces\PasswordCredentialRepository;
use wpdb;

use function dbDelta;

/**
 * $wpdb-backed implementation of {@see PasswordCredentialRepository}.
 *
 * Schema is created via dbDelta (see install()), which Unity runs on a
 * version change rather than on activation: Unity is already active
 * wherever Reach or Fellowship are, and an activation hook would never
 * fire on the upgrade that introduced this table.
 *
 * One row per member email; holds only hashed secrets (bcrypt password
 * hash, SHA-256 reset-token hash) plus the lockout counters — never any
 * raw password or raw token.
 *
 * <b>install() also absorbs the two tables this replaces.</b> Reach and
 * Fellowship each had one of their own, with byte-identical schemas, and
 * a member could hold a different password in each. See
 * {@see absorb()} for which row wins when both hold one.
 *
 * Writes use INSERT … ON DUPLICATE KEY UPDATE so the first password
 * reset for a member (who has no row yet) and every later change go
 * through the same code path, keyed on the email primary key.
 *
 * Every write here guards wpdb::prepare() against null, which is why the
 * SQL goes into a local before reaching query() rather than being nested
 * inline. prepare() returns null only when the query carries no
 * placeholders or the arguments do not match them — a coding error, never
 * a runtime condition — so the guards throw rather than skipping the
 * write. These statements are the source of truth for password sign-in;
 * silently issuing no query at all would leave a caller believing a
 * password or lockout counter had been stored when it had not.
 */
final class WpdbPasswordCredentialRepository implements PasswordCredentialRepository
{
    public const TABLE_SUFFIX = 'unity_credentials';

    public function __construct(private readonly wpdb $wpdb)
    {
    }

    /**
     * @return literal-string
     *
     * wpdb::prepare() only accepts a literal-string query, and every query in
     * this class interpolates this table name. PHPStan types $wpdb->prefix as
     * a plain string so it cannot derive that on its own — the annotation
     * asserts it. It holds: the prefix comes from wp-config.php and the suffix
     * is a class constant, so no part of this is reachable from user input.
     */
    public static function tableName(wpdb $wpdb): string
    {
        // Asserted on the prefix rather than on the concatenation: PHPStan
        // infers the joined string as non-falsy-string, which literal-string
        // is not a subtype of, so a @var on the result is rejected outright.
        /** @var literal-string $prefix */
        $prefix = $wpdb->prefix;

        return $prefix . self::TABLE_SUFFIX;
    }

    /**
     * Idempotent: safe to call on every activation. dbDelta diffs against
     * the live schema and only applies changes.
     */
    public static function install(wpdb $wpdb): void
    {
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        $table   = self::tableName($wpdb);
        $charset = $wpdb->get_charset_collate();
        $prefix  = $wpdb->prefix;

        $sql = "CREATE TABLE {$table} (
            email VARCHAR(254) NOT NULL,
            password_hash VARCHAR(255) NOT NULL DEFAULT '',
            reset_token_hash CHAR(64) NOT NULL DEFAULT '',
            reset_expires_at BIGINT UNSIGNED NOT NULL DEFAULT 0,
            failed_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            locked_until BIGINT UNSIGNED NOT NULL DEFAULT 0,
            updated_at BIGINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY  (email),
            KEY reset_token_hash (reset_token_hash)
        ) {$charset};";

        dbDelta($sql);

        self::absorb($wpdb, $prefix . 'reach_credentials');
        self::absorb($wpdb, $prefix . 'fellowship_credentials');
    }

    /**
     * Copy any rows from a plugin's old private credentials table into this
     * one, then leave the old table alone.
     *
     * <b>The newer row wins, and neither is overwritten blind.</b> A member
     * who set a password in Reach and later set one in Fellowship holds two
     * different hashes, and only one of them is the password they think
     * they have. `updated_at` is what says which, so the INSERT carries an
     * ON DUPLICATE KEY UPDATE guarded on it: a row already here from the
     * other table is replaced only by a strictly newer one. Running this
     * twice, or in either order, therefore reaches the same answer.
     *
     * <b>The old tables are not dropped.</b> This is the only copy of some
     * members' passwords, the migration runs unattended on an admin page
     * load, and a bad one that has also destroyed its source is not
     * recoverable. They are left in place, unread, for somebody to remove
     * deliberately once this has been seen to work.
     *
     * Absent tables are the normal case — a site with Reach but not
     * Fellowship has only one — so a missing table is silence, not a
     * failure.
     */
    private static function absorb(wpdb $wpdb, string $legacyTable): void
    {
        $table = self::tableName($wpdb);

        // SHOW TABLES LIKE rather than information_schema: it needs no extra
        // grant, and a shared host may not give one.
        $sql = $wpdb->prepare('SHOW TABLES LIKE %s', $legacyTable);

        if ($sql === null || $wpdb->get_var($sql) !== $legacyTable) {
            return;
        }

        /**
         * @var literal-string $legacy
         *
         * Built from $wpdb->prefix and a literal suffix chosen by the two
         * call sites above, and proved to exist by the SHOW TABLES check —
         * no part of it is reachable from user input.
         */
        $legacy = $legacyTable;

        $wpdb->query(
            "INSERT INTO {$table}
                 (email, password_hash, reset_token_hash, reset_expires_at,
                  failed_attempts, locked_until, updated_at)
             SELECT email, password_hash, reset_token_hash, reset_expires_at,
                    failed_attempts, locked_until, updated_at
               FROM {$legacy}
             ON DUPLICATE KEY UPDATE
                 password_hash = IF(VALUES(updated_at) > {$table}.updated_at, VALUES(password_hash), {$table}.password_hash),
                 reset_token_hash = IF(VALUES(updated_at) > {$table}.updated_at, VALUES(reset_token_hash), {$table}.reset_token_hash),
                 reset_expires_at = IF(VALUES(updated_at) > {$table}.updated_at, VALUES(reset_expires_at), {$table}.reset_expires_at),
                 failed_attempts = IF(VALUES(updated_at) > {$table}.updated_at, VALUES(failed_attempts), {$table}.failed_attempts),
                 locked_until = IF(VALUES(updated_at) > {$table}.updated_at, VALUES(locked_until), {$table}.locked_until),
                 updated_at = GREATEST({$table}.updated_at, VALUES(updated_at))"
        );
    }

    public function find(string $email): ?PasswordCredential
    {
        $table = self::tableName($this->wpdb);
        $row = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT email, password_hash, reset_token_hash, reset_expires_at,
                    failed_attempts, locked_until, updated_at
               FROM {$table}
              WHERE email = %s
              LIMIT 1",
            $email,
        ), ARRAY_A);

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function all(int $limit = 500): array
    {
        $table = self::tableName($this->wpdb);

        // Ordered by updated_at rather than by email: what an admin is
        // looking at this screen for is recent activity - who has just
        // been locked out, whose reset is outstanding - and an
        // alphabetical list buries that.
        $sql = $this->wpdb->prepare(
            "SELECT email, password_hash, reset_token_hash, reset_expires_at,
                    failed_attempts, locked_until, updated_at
               FROM {$table}
              ORDER BY updated_at DESC
              LIMIT %d",
            max(1, $limit),
        );

        if ($sql === null) {
            throw new LogicException('Failed to prepare the credential listing query.');
        }

        $rows = $this->wpdb->get_results($sql, ARRAY_A);

        if (!is_array($rows)) {
            return [];
        }

        $credentials = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $credentials[] = $this->hydrate($row);
            }
        }

        return $credentials;
    }

    public function findByResetTokenHash(string $tokenHash): ?PasswordCredential
    {
        // An empty hash would otherwise match every reset-free row; refuse
        // it outright so a blank token can never resolve to a credential.
        if ($tokenHash === '') {
            return null;
        }

        $table = self::tableName($this->wpdb);
        $row = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT email, password_hash, reset_token_hash, reset_expires_at,
                    failed_attempts, locked_until, updated_at
               FROM {$table}
              WHERE reset_token_hash = %s
              LIMIT 1",
            $tokenHash,
        ), ARRAY_A);

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function upsertPasswordHash(string $email, string $passwordHash, int $now): void
    {
        $table = self::tableName($this->wpdb);

        // Setting a password clears any pending reset token and unlocks the
        // account in the same statement — a successful set/reset is a clean
        // slate.
        $sql = $this->wpdb->prepare(
            "INSERT INTO {$table}
                 (email, password_hash, reset_token_hash, reset_expires_at,
                  failed_attempts, locked_until, updated_at)
             VALUES (%s, %s, '', 0, 0, 0, %d)
             ON DUPLICATE KEY UPDATE
                 password_hash = VALUES(password_hash),
                 reset_token_hash = '',
                 reset_expires_at = 0,
                 failed_attempts = 0,
                 locked_until = 0,
                 updated_at = VALUES(updated_at)",
            $email,
            $passwordHash,
            $now,
        );

        if ($sql === null) {
            throw new LogicException('Failed to prepare the password upsert query.');
        }

        $this->wpdb->query($sql);
    }

    public function storeResetToken(string $email, string $tokenHash, int $expiresAt, int $now): void
    {
        $table = self::tableName($this->wpdb);

        $sql = $this->wpdb->prepare(
            "INSERT INTO {$table}
                 (email, reset_token_hash, reset_expires_at, updated_at)
             VALUES (%s, %s, %d, %d)
             ON DUPLICATE KEY UPDATE
                 reset_token_hash = VALUES(reset_token_hash),
                 reset_expires_at = VALUES(reset_expires_at),
                 updated_at = VALUES(updated_at)",
            $email,
            $tokenHash,
            $expiresAt,
            $now,
        );

        if ($sql === null) {
            throw new LogicException('Failed to prepare the reset-token store query.');
        }

        $this->wpdb->query($sql);
    }

    public function clearResetToken(string $email, int $now): void
    {
        $table = self::tableName($this->wpdb);

        $sql = $this->wpdb->prepare(
            "UPDATE {$table}
                SET reset_token_hash = '', reset_expires_at = 0, updated_at = %d
              WHERE email = %s",
            $now,
            $email,
        );

        if ($sql === null) {
            throw new LogicException('Failed to prepare the reset-token clear query.');
        }

        $this->wpdb->query($sql);
    }

    public function recordFailedAttempt(string $email, int $failedAttempts, int $lockedUntil, int $now): void
    {
        $table = self::tableName($this->wpdb);

        // UPDATE only — an unknown email has no password to guess, so we
        // never create a row for it (that would leak existence and let an
        // attacker seed the table).
        $sql = $this->wpdb->prepare(
            "UPDATE {$table}
                SET failed_attempts = %d, locked_until = %d, updated_at = %d
              WHERE email = %s",
            $failedAttempts,
            $lockedUntil,
            $now,
            $email,
        );

        if ($sql === null) {
            throw new LogicException('Failed to prepare the failed-attempt query.');
        }

        $this->wpdb->query($sql);
    }

    public function resetFailedAttempts(string $email, int $now): void
    {
        $table = self::tableName($this->wpdb);

        $sql = $this->wpdb->prepare(
            "UPDATE {$table}
                SET failed_attempts = 0, locked_until = 0, updated_at = %d
              WHERE email = %s",
            $now,
            $email,
        );

        if ($sql === null) {
            throw new LogicException('Failed to prepare the attempt-reset query.');
        }

        $this->wpdb->query($sql);
    }

    public function delete(string $email): void
    {
        $this->wpdb->delete(self::tableName($this->wpdb), ['email' => $email], ['%s']);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): PasswordCredential
    {
        return new PasswordCredential(
            (string) $row['email'],
            (string) $row['password_hash'],
            (string) $row['reset_token_hash'],
            (int) $row['reset_expires_at'],
            (int) $row['failed_attempts'],
            (int) $row['locked_until'],
            (int) $row['updated_at'],
        );
    }
}
