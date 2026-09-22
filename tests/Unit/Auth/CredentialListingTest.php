<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Auth;

use Unity\Auth\PasswordCredential;
use Unity\Testing\Doubles\InMemoryPasswordCredentialRepository;

/*
 * Listing the store, which is what the admin screen in Amber reads.
 *
 * <p>Asserted against the double rather than the SQL, because what the
 * screen depends on is the ordering and the bound — and the double is
 * what the screen's own tests will run against, so the two had better
 * agree about both.</p>
 */

function listedCredential(string $email, int $updatedAt): PasswordCredential
{
    return new PasswordCredential($email, 'hash', '', 0, 0, 0, $updatedAt);
}

// Newest change first. An alphabetical list buries the thing an admin
// opened the screen for: who has just been locked out, whose reset is
// still outstanding.
it('lists the most recently changed first', function () {
    $repository = new InMemoryPasswordCredentialRepository([
        listedCredential('old@example.test', 1000),
        listedCredential('newest@example.test', 3000),
        listedCredential('middle@example.test', 2000),
    ]);

    $emails = array_map(
        static fn(PasswordCredential $c): string => $c->email,
        $repository->all()
    );

    expect($emails)->toBe(['newest@example.test', 'middle@example.test', 'old@example.test']);
});

it('honours the bound', function () {
    $repository = new InMemoryPasswordCredentialRepository([
        listedCredential('a@example.test', 1000),
        listedCredential('b@example.test', 2000),
        listedCredential('c@example.test', 3000),
    ]);

    expect($repository->all(2))->toHaveCount(2);
});

// A limit of zero or less is a caller error, not a request for
// nothing: answering an empty list would read on the screen exactly
// as "no member has a password", which is a different and alarming
// statement.
it('still answers something for a nonsense bound', function () {
    $repository = new InMemoryPasswordCredentialRepository([
        listedCredential('a@example.test', 1000),
    ]);

    expect($repository->all(0))->toHaveCount(1)
        ->and($repository->all(-5))->toHaveCount(1);
});

it('lists nothing from an empty store', function () {
    expect((new InMemoryPasswordCredentialRepository())->all())->toBe([]);
});
