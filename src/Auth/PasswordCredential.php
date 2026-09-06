<?php

declare(strict_types=1);

namespace Unity\Auth;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * A member's password credential row.
 *
 * Immutable snapshot of one row of the {@see PasswordCredentialRepository}
 * store. Members authenticate primarily via OAuth, so a credential only
 * exists for members who have set a password through the emailed
 * reset/set-password flow.
 *
 * <b>One store, shared.</b> This lives in Unity rather than in Reach or
 * Fellowship because a member has one password, not one per app. Both
 * plugins previously carried an identical copy of this class and an
 * identical table, which meant a member who set a password in Reach could
 * not sign into Link with it, and a reset in one place left the other
 * unchanged with nothing to say so. Unity owns member data, and both
 * plugins already require it, so this is where the one answer belongs.
 *
 * The stored secrets are never the raw values: `passwordHash` is a bcrypt
 * hash ({@see password_hash()}) and `resetTokenHash` is the SHA-256 hex of
 * the one-time token that was mailed out — the raw token lives only in the
 * link the member clicks. A database dump alone therefore yields neither a
 * usable password nor a usable reset link.
 */
final class PasswordCredential
{
    public function __construct(
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly string $resetTokenHash,
        public readonly int $resetExpiresAt,
        public readonly int $failedAttempts,
        public readonly int $lockedUntil,
        public readonly int $updatedAt,
    ) {
    }

    /** Whether a usable password has been set for this member. */
    public function hasPassword(): bool
    {
        return $this->passwordHash !== '';
    }

    /** Whether the account is currently locked out after failed logins. */
    public function isLocked(int $now): bool
    {
        return $this->lockedUntil > $now;
    }

    /** Whether an unexpired reset token is on file. */
    public function hasValidResetToken(int $now): bool
    {
        return $this->resetTokenHash !== '' && $this->resetExpiresAt > $now;
    }
}
