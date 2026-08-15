<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use LogicException;
use Unity\Groups\Interfaces\Group;
use Unity\Groups\Interfaces\GroupRepository;

/**
 * An array-backed GroupRepository for tests.
 *
 * The group-shaped counterpart to {@see InMemoryMemberRepository}, and it
 * follows the same write policy: by default mutations are recorded and
 * reported successful, and rejectWrites: true makes each of them throw, so a
 * read-only collaborator that starts writing fails loudly rather than
 * silently passing.
 */
final class InMemoryGroupRepository implements GroupRepository
{
    /** @var array<int, Group> */
    private array $groups;

    /**
     * Groups passed to save(), in order.
     *
     * @var array<int, Group>
     */
    public array $saved = [];

    /**
     * Groups passed to update(), in order.
     *
     * @var array<int, Group>
     */
    public array $updated = [];

    /**
     * Ids passed to delete(), in order.
     *
     * @var array<int, int>
     */
    public array $deleted = [];

    /**
     * @param array<int, Group> $groups
     * @param bool $rejectWrites Throw from every mutation instead of recording it.
     */
    public function __construct(array $groups = [], private bool $rejectWrites = false)
    {
        $this->groups = array_values($groups);
    }

    public function findById(int $id): ?Group
    {
        foreach ($this->groups as $group) {
            if ($group->getId() === $id) {
                return $group;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $args Accepted and ignored — the real
     *                                   repository takes WP_Query arguments,
     *                                   which a double cannot honour usefully.
     * @return array<int, Group>
     */
    public function findAll(array $args = []): array
    {
        return $this->groups;
    }

    /**
     * @param array<string, mixed> $args
     */
    public function count(array $args = []): int
    {
        return count($this->groups);
    }

    public function save(Group $group): bool
    {
        if ($this->rejectWrites) {
            throw new LogicException('InMemoryGroupRepository::save() called on a read-only double.');
        }

        $this->saved[] = $group;

        return true;
    }

    public function update(Group $group): bool
    {
        if ($this->rejectWrites) {
            throw new LogicException('InMemoryGroupRepository::update() called on a read-only double.');
        }

        $this->updated[] = $group;

        return true;
    }

    public function delete(int $id): bool
    {
        if ($this->rejectWrites) {
            throw new LogicException('InMemoryGroupRepository::delete() called on a read-only double.');
        }

        $this->deleted[] = $id;
        $this->groups = array_values(array_filter(
            $this->groups,
            static fn (Group $group): bool => $group->getId() !== $id
        ));

        return true;
    }
}
