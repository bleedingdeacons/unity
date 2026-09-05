<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Auth\Interfaces\PasswordCredentialRepository;
use Unity\Auth\PasswordCredential;

/**
 * An array-backed PasswordCredentialRepository for tests.
 *
 * Ships here rather than in each consumer's test suite for the reason the
 * store itself does: Reach and Fellowship both authenticate against it,
 * and two independently written doubles would be two guesses at the same
 * behaviour. The awkward parts are the ones worth agreeing on —
 * {@see upsertPasswordHash()} clears the reset token and the lockout,
 * {@see recordFailedAttempt()} never creates a row — because an
 * authenticator tested against a double that gets either wrong passes
 * while the real thing does not.
 *
 * Rows are keyed exactly as given. Callers normalise the email to
 * lowercase before it reaches the store, and this does not do it for
 * them: a test that passes a mixed-case address should see the same miss
 * the database would give it.
 */
final class InMemoryPasswordCredentialRepository implements PasswordCredentialRepository
{
    /** @var array<string, PasswordCredential> */
    private array $rows = [];

    /**
     * @param array<int, PasswordCredential> $credentials
     */
    public function __construct(array $credentials = [])
    {
        foreach ($credentials as $credential) {
            $this->rows[$credential->email] = $credential;
        }
    }

    /** @var array<int, string> Emails passed to delete(), in order. */
    public array $deleted = [];

    public function find(string $email): ?PasswordCredential
    {
        return $this->rows[$email] ?? null;
    }

    public function findByResetTokenHash(string $tokenHash): ?PasswordCredential
    {
        // An empty hash matches nothing, as it does in the real store —
        // there it would otherwise match every reset-free row.
        if ($tokenHash === '') {
            return null;
        }

        foreach ($this->rows as $row) {
            if ($row->resetTokenHash === $tokenHash) {
                return $row;
            }
        }

        return null;
    }

    public function upsertPasswordHash(string $email, string $passwordHash, int $now): void
    {
        // A set or reset is a clean slate: the pending token goes and so
        // does the lockout, in one step, exactly as the SQL does it.
        $this->rows[$email] = new PasswordCredential($email, $passwordHash, '', 0, 0, 0, $now);
    }

    public function storeResetToken(string $email, string $tokenHash, int $expiresAt, int $now): void
    {
        // Creates the row when there is none, which is what the real
        // store's INSERT ... ON DUPLICATE KEY UPDATE does: a member's
        // first reset request is the thing that brings their row into
        // existence. What it must not do is disturb a password already
        // set, or an in-progress lockout.
        $existing = $this->rows[$email] ?? new PasswordCredential($email, '', '', 0, 0, 0, 0);

        $this->rows[$email] = new PasswordCredential(
            $email,
            $existing->passwordHash,
            $tokenHash,
            $expiresAt,
            $existing->failedAttempts,
            $existing->lockedUntil,
            $now,
        );
    }

    public function clearResetToken(string $email, int $now): void
    {
        $existing = $this->rows[$email] ?? null;

        if ($existing === null) {
            return;
        }

        $this->rows[$email] = new PasswordCredential(
            $email,
            $existing->passwordHash,
            '',
            0,
            $existing->failedAttempts,
            $existing->lockedUntil,
            $now,
        );
    }

    /**
     * Never creates a row. An unknown email has no password to guess, and
     * seeding one both leaks that the address is unknown and lets an
     * attacker fill the table.
     */
    public function recordFailedAttempt(string $email, int $failedAttempts, int $lockedUntil, int $now): void
    {
        $existing = $this->rows[$email] ?? null;

        if ($existing === null) {
            return;
        }

        $this->rows[$email] = new PasswordCredential(
            $email,
            $existing->passwordHash,
            $existing->resetTokenHash,
            $existing->resetExpiresAt,
            $failedAttempts,
            $lockedUntil,
            $now,
        );
    }

    public function resetFailedAttempts(string $email, int $now): void
    {
        $existing = $this->rows[$email] ?? null;

        if ($existing === null) {
            return;
        }

        $this->rows[$email] = new PasswordCredential(
            $email,
            $existing->passwordHash,
            $existing->resetTokenHash,
            $existing->resetExpiresAt,
            0,
            0,
            $now,
        );
    }

    public function delete(string $email): void
    {
        $this->deleted[] = $email;

        unset($this->rows[$email]);
    }
}
