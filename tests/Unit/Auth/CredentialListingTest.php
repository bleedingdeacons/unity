<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use Unity\Auth\PasswordCredential;
use Unity\Testing\Doubles\InMemoryPasswordCredentialRepository;

/**
 * Listing the store, which is what the admin screen in Amber reads.
 *
 * <p>Asserted against the double rather than the SQL, because what the
 * screen depends on is the ordering and the bound — and the double is
 * what the screen's own tests will run against, so the two had better
 * agree about both.</p>
 */
final class CredentialListingTest extends TestCase
{
    private static function credential(string $email, int $updatedAt): PasswordCredential
    {
        return new PasswordCredential($email, 'hash', '', 0, 0, 0, $updatedAt);
    }

    /**
     * Newest change first. An alphabetical list buries the thing an admin
     * opened the screen for: who has just been locked out, whose reset is
     * still outstanding.
     *
     * @test
     */
    public function it_lists_the_most_recently_changed_first(): void
    {
        $repository = new InMemoryPasswordCredentialRepository([
            self::credential('old@example.test', 1000),
            self::credential('newest@example.test', 3000),
            self::credential('middle@example.test', 2000),
        ]);

        $emails = array_map(
            static fn(PasswordCredential $c): string => $c->email,
            $repository->all()
        );

        $this->assertSame(
            ['newest@example.test', 'middle@example.test', 'old@example.test'],
            $emails
        );
    }

    /**
     * @test
     */
    public function it_honours_the_bound(): void
    {
        $repository = new InMemoryPasswordCredentialRepository([
            self::credential('a@example.test', 1000),
            self::credential('b@example.test', 2000),
            self::credential('c@example.test', 3000),
        ]);

        $this->assertCount(2, $repository->all(2));
    }

    /**
     * A limit of zero or less is a caller error, not a request for
     * nothing: answering an empty list would read on the screen exactly
     * as "no member has a password", which is a different and alarming
     * statement.
     *
     * @test
     */
    public function a_nonsense_bound_still_answers_something(): void
    {
        $repository = new InMemoryPasswordCredentialRepository([
            self::credential('a@example.test', 1000),
        ]);

        $this->assertCount(1, $repository->all(0));
        $this->assertCount(1, $repository->all(-5));
    }

    /**
     * @test
     */
    public function an_empty_store_lists_nothing(): void
    {
        $this->assertSame([], (new InMemoryPasswordCredentialRepository())->all());
    }
}
