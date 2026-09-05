<?php

declare(strict_types=1);

namespace Unity\Auth\Interfaces;

if (!defined('ABSPATH')) {
    exit;
}

use Unity\Auth\PasswordCredential;

/**
 * Persistence for member password credentials.
 *
 * Keyed by the member's email (normalised lowercase). A row is created
 * lazily the first time a member requests a password reset or sets a
 * password — most members never have one, since OAuth is the default
 * sign-in path in both Reach and Fellowship.
 *
 * <b>Unity owns this, and that is the point.</b> A member has one
 * password, not one per app. Reach and Fellowship each carried an
 * identical copy of this contract over an identical table of their own;
 * the consequence was that a password set in one was invisible to the
 * other, and a reset in one left the other's row stale with nothing
 * anywhere to say so. Both plugins require Unity, so the one answer lives
 * here and both resolve it from Unity's container.
 *
 * Bound to {@see \Unity\Auth\WpdbPasswordCredentialRepository}; split
 * behind an interface so authenticators can be unit-tested against an
 * in-memory fake.
 */
interface PasswordCredentialRepository
{
    /** Load the credential for an email, or null if none exists. */
    public function find(string $email): ?PasswordCredential;

    /**
     * Every credential in the store, newest change first.
     *
     * <p>For the admin screen that answers "who has a password, and who
     * is locked out". It is a whole-table read, which is why it is
     * bounded: the alternative an admin screen would otherwise reach for
     * is a find() per member, and that is a query per row of a list.</p>
     *
     * <p>Most members never have a row — OAuth is the default sign-in
     * path in both consumers — so this is a short list on any real site,
     * and the limit is a guard rather than pagination.</p>
     *
     * @return list<PasswordCredential>
     */
    public function all(int $limit = 500): array;

    /**
     * Load the credential holding the given reset-token hash, or null.
     * The hash is the SHA-256 hex of the raw token from the reset link.
     */
    public function findByResetTokenHash(string $tokenHash): ?PasswordCredential;

    /**
     * Set (or replace) the password hash for an email, creating the row if
     * needed. Also clears any pending reset token and unlocks the account —
     * a successful set/reset is a fresh start.
     */
    public function upsertPasswordHash(string $email, string $passwordHash, int $now): void;

    /**
     * Store a pending reset-token hash + expiry for an email, creating the
     * row if needed. Leaves any existing password hash untouched.
     */
    public function storeResetToken(string $email, string $tokenHash, int $expiresAt, int $now): void;

    /** Clear any pending reset token for an email. */
    public function clearResetToken(string $email, int $now): void;

    /**
     * Persist the running failed-attempt count and lockout deadline for an
     * existing credential. Never creates a row — unknown emails have no
     * password to guess and must not be seeded into the table.
     */
    public function recordFailedAttempt(string $email, int $failedAttempts, int $lockedUntil, int $now): void;

    /** Zero the failed-attempt count and lockout after a successful login. */
    public function resetFailedAttempts(string $email, int $now): void;

    /**
     * Delete the credential for an email. Used to erase this GDPR-protected
     * personal data when the member is deleted. No-op if no row exists.
     */
    public function delete(string $email): void;
}
