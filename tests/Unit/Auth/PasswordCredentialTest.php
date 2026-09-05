<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use Unity\Auth\PasswordCredential;

/**
 * The three questions a credential row can answer about itself.
 *
 * <p>All three are read on the sign-in path, where getting one wrong is
 * either a member locked out of an account that should open or an
 * attacker let past a lockout that should hold.</p>
 */
final class PasswordCredentialTest extends TestCase
{
    private static function credential(
        string $passwordHash = 'hashed',
        string $resetTokenHash = '',
        int $resetExpiresAt = 0,
        int $lockedUntil = 0,
    ): PasswordCredential {
        return new PasswordCredential(
            'member@example.test',
            $passwordHash,
            $resetTokenHash,
            $resetExpiresAt,
            0,
            $lockedUntil,
            1000,
        );
    }

    /**
     * @test
     */
    public function a_row_with_no_hash_has_no_password(): void
    {
        $this->assertFalse(self::credential(passwordHash: '')->hasPassword());
        $this->assertTrue(self::credential()->hasPassword());
    }

    /**
     * The boundary is exclusive: a lockout that expires exactly now has
     * expired. Anything else leaves a member locked out for one more
     * second than they were told.
     *
     * @test
     */
    public function a_lockout_holds_until_its_deadline_and_no_longer(): void
    {
        $credential = self::credential(lockedUntil: 2000);

        $this->assertTrue($credential->isLocked(1999));
        $this->assertFalse($credential->isLocked(2000));
        $this->assertFalse($credential->isLocked(2001));
    }

    /**
     * @test
     */
    public function an_unlocked_row_is_never_locked(): void
    {
        $this->assertFalse(self::credential()->isLocked(0));
    }

    /**
     * Both halves matter. An expired token is no token, and so is an
     * absent one — and an absent one with a future expiry, which a
     * half-cleared row could hold, must not read as valid.
     *
     * @test
     */
    public function a_reset_token_is_valid_only_when_present_and_unexpired(): void
    {
        $this->assertTrue(self::credential(resetTokenHash: 'abc', resetExpiresAt: 2000)->hasValidResetToken(1999));
        $this->assertFalse(self::credential(resetTokenHash: 'abc', resetExpiresAt: 2000)->hasValidResetToken(2000));
        $this->assertFalse(self::credential(resetTokenHash: '', resetExpiresAt: 9999)->hasValidResetToken(1));
        $this->assertFalse(self::credential()->hasValidResetToken(1));
    }
}
