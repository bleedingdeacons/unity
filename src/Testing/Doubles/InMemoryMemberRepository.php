<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use LogicException;
use Unity\Members\Interfaces\Member;
use Unity\Members\Interfaces\MemberRepository;

/**
 * An array-backed MemberRepository for tests.
 *
 * Seven of these existed across the suite before this one — three of them
 * independently named InMemoryMemberRepository — and they agreed on the reads
 * but not the writes: Scrutiny's threw from every mutation so that a pruner
 * which accidentally started writing would fail loudly, while Trusted's
 * returned true so that a service under test could run to completion.
 *
 * Both are supported. By default writes are recorded and reported successful;
 * pass rejectWrites: true for the fail-loudly variant.
 *
 * The reads model the real repository closely enough to drive collaborators
 * for real rather than through a stubbed approximation — in particular
 * findTelephoneResponders() filters on the flag, so a member who is not a
 * responder is visible to findAll() but not to that call.
 */
final class InMemoryMemberRepository implements MemberRepository
{
    /** @var array<int, Member> */
    private array $members;

    /**
     * Anonymous names passed to create(), in order.
     *
     * @var array<int, string>
     */
    public array $created = [];

    /**
     * Members passed to save(), in order.
     *
     * @var array<int, Member>
     */
    public array $saved = [];

    /**
     * Members passed to update(), in order.
     *
     * @var array<int, Member>
     */
    public array $updated = [];

    /**
     * Ids passed to delete(), in order.
     *
     * @var array<int, int>
     */
    public array $deleted = [];

    /**
     * @param array<int, Member> $members
     * @param bool $rejectWrites Throw from every mutation instead of recording it.
     */
    public function __construct(array $members = [], private bool $rejectWrites = false)
    {
        $this->members = array_values($members);
    }

    public function findById(int $id): ?Member
    {
        foreach ($this->members as $member) {
            if ($member->getId() === $id) {
                return $member;
            }
        }

        return null;
    }

    /**
     * Case-insensitively, as the real repository is.
     *
     * TsmlMemberRepository::findByEmail() runs a meta_query with
     * compare => '=', and WordPress's meta tables carry a _ci collation, so
     * MySQL matches without regard to case. Reach's fakes modelled that with
     * strcasecmp() and were right to; an exact comparison here would be
     * stricter than production and could fail a test that would pass live.
     */
    public function findByEmail(string $email): ?Member
    {
        foreach ($this->members as $member) {
            if (strcasecmp($member->getPersonalEmail(), $email) === 0) {
                return $member;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Member>
     */
    public function findAll(array $args = []): array
    {
        return $this->members;
    }

    /** @return array<int, Member> */
    public function findTelephoneResponders(): array
    {
        return array_values(array_filter(
            $this->members,
            static fn (Member $member): bool => $member->isTelephoneResponder()
        ));
    }

    /** @param array<string, mixed> $args */
    public function count(array $args = []): int
    {
        return count($this->members);
    }

    /**
     * Append a member carrying only the anonymous name, as the real repository
     * does, and answer with its id — the two-phase create-then-save flow.
     */
    public function create(string $anonymousName): int
    {
        $this->guardWrites();

        $this->created[] = $anonymousName;

        $id = $this->nextId();
        $this->members[] = new MemberStub(id: $id, anonymousName: $anonymousName);

        return $id;
    }

    public function save(Member $member): bool
    {
        $this->guardWrites();

        $this->saved[] = $member;
        $this->replace($member);

        return true;
    }

    public function delete(int $id): bool
    {
        $this->guardWrites();

        $this->deleted[] = $id;

        $this->members = array_values(array_filter(
            $this->members,
            static fn (Member $member): bool => $member->getId() !== $id
        ));

        return true;
    }

    public function update(Member $member): bool
    {
        $this->guardWrites();

        $this->updated[] = $member;
        $this->replace($member);

        return true;
    }

    private function guardWrites(): void
    {
        if ($this->rejectWrites) {
            throw new LogicException('Not implemented in test double');
        }
    }

    /** Swap in the stored member with the same id, if there is one. */
    private function replace(Member $member): void
    {
        foreach ($this->members as $index => $existing) {
            if ($existing->getId() === $member->getId()) {
                $this->members[$index] = $member;

                return;
            }
        }
    }

    private function nextId(): int
    {
        $ids = array_map(static fn (Member $member): int => $member->getId(), $this->members);

        return $ids === [] ? 1 : max($ids) + 1;
    }
}
