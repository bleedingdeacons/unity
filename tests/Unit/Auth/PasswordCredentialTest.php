<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Auth;

use Unity\Auth\PasswordCredential;

/*
 * The three questions a credential row can answer about itself.
 *
 * <p>All three are read on the sign-in path, where getting one wrong is
 * either a member locked out of an account that should open or an
 * attacker let past a lockout that should hold.</p>
 */

function passwordCredential(
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

it('has no password when the row has no hash', function () {
    expect(passwordCredential(passwordHash: '')->hasPassword())->toBeFalse()
        ->and(passwordCredential()->hasPassword())->toBeTrue();
});

// The boundary is exclusive: a lockout that expires exactly now has
// expired. Anything else leaves a member locked out for one more
// second than they were told.
it('holds a lockout until its deadline and no longer', function () {
    $credential = passwordCredential(lockedUntil: 2000);

    expect($credential->isLocked(1999))->toBeTrue()
        ->and($credential->isLocked(2000))->toBeFalse()
        ->and($credential->isLocked(2001))->toBeFalse();
});

it('never treats an unlocked row as locked', function () {
    expect(passwordCredential()->isLocked(0))->toBeFalse();
});

// Both halves matter. An expired token is no token, and so is an
// absent one — and an absent one with a future expiry, which a
// half-cleared row could hold, must not read as valid.
it('treats a reset token as valid only when present and unexpired', function () {
    expect(passwordCredential(resetTokenHash: 'abc', resetExpiresAt: 2000)->hasValidResetToken(1999))->toBeTrue()
        ->and(passwordCredential(resetTokenHash: 'abc', resetExpiresAt: 2000)->hasValidResetToken(2000))->toBeFalse()
        ->and(passwordCredential(resetTokenHash: '', resetExpiresAt: 9999)->hasValidResetToken(1))->toBeFalse()
        ->and(passwordCredential()->hasValidResetToken(1))->toBeFalse();
});
