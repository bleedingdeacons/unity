<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Committees\Interfaces\Committee;
use Unity\Committees\Interfaces\CommitteeRepository;

/**
 * An in-memory CommitteeRepository for tests.
 *
 * Built from a flat list of committees plus two assignment maps, and does the
 * tree walking itself, so a consumer testing "who is on this committee"
 * describes the shape it wants rather than standing up a taxonomy.
 *
 * It is a real implementation of the hierarchy, not a stub that returns
 * whatever it was handed: descendants, ancestors and paths are computed from
 * the parent ids. That is the point — a mocked `descendantsOf()` proves the
 * consumer called it, while this proves the consumer got the branch right.
 *
 * <b>Assignments are keyed by slug, matching the interface.</b> Term ids differ
 * between sites, and a double that made ids the natural key would quietly
 * encourage consumers to build against the one thing the real repository warns
 * them off.
 *
 * <b>Cycle-safe.</b> The real hierarchy can be edited into a loop in wp-admin,
 * so every walk here carries a visited set. A double that hangs on data the
 * real thing merely mis-draws would be worse than useless in a test suite.
 */
class InMemoryCommitteeRepository implements CommitteeRepository
{
    /** @var array<int, Committee> */
    private array $committees;

    /** @var array<string, array<int, int>> */
    private array $memberIds;

    /** @var array<string, array<int, int>> */
    private array $positionIds;

    /**
     * @param array<int, Committee>            $committees  Flat; nesting comes
     *                                                      from their parent ids
     * @param array<string, array<int, int>>   $memberIds   Member post ids by
     *                                                      committee slug
     * @param array<string, array<int, int>>   $positionIds Position post ids by
     *                                                      committee slug
     */
    public function __construct(
        array $committees = [],
        array $memberIds = [],
        array $positionIds = []
    ) {
        $this->committees  = $committees;
        $this->memberIds   = $memberIds;
        $this->positionIds = $positionIds;
    }

    public function findById(int $id): ?Committee
    {
        foreach ($this->committees as $committee) {
            if ($committee->getId() === $id) {
                return $committee;
            }
        }

        return null;
    }

    public function findBySlug(string $slug): ?Committee
    {
        if ($slug === '') {
            return null;
        }

        foreach ($this->committees as $committee) {
            if ($committee->getSlug() === $slug) {
                return $committee;
            }
        }

        return null;
    }

    public function findAll(): array
    {
        return $this->sorted($this->committees);
    }

    public function roots(): array
    {
        return $this->childrenOfId(0);
    }

    public function childrenOf(int|string $committee): array
    {
        $id = $this->resolveId($committee);

        return $id === 0 ? [] : $this->childrenOfId($id);
    }

    public function descendantsOf(int|string $committee): array
    {
        $id = $this->resolveId($committee);

        if ($id === 0) {
            return [];
        }

        $found = [];
        $this->collectDescendants($id, $found, []);

        return $this->sorted($found);
    }

    public function ancestorsOf(int|string $committee): array
    {
        $id = $this->resolveId($committee);
        $self = $id === 0 ? null : $this->findById($id);

        if ($self === null) {
            return [];
        }

        // Nearest parent first, matching the interface.
        $ancestors = [];
        $seen      = [$id => true];
        $parentId  = $self->getParentId();

        while ($parentId !== 0 && !isset($seen[$parentId])) {
            $parent = $this->findById($parentId);

            if ($parent === null) {
                break;
            }

            $seen[$parentId] = true;
            $ancestors[]     = $parent;
            $parentId        = $parent->getParentId();
        }

        return $ancestors;
    }

    public function pathTo(int|string $committee): array
    {
        $id   = $this->resolveId($committee);
        $self = $id === 0 ? null : $this->findById($id);

        if ($self === null) {
            return [];
        }

        $path   = array_reverse($this->ancestorsOf($id));
        $path[] = $self;

        return $path;
    }

    public function forMember(int $memberId): array
    {
        return $this->assignedTo($memberId, $this->memberIds);
    }

    public function forPosition(int $positionId): array
    {
        return $this->assignedTo($positionId, $this->positionIds);
    }

    public function memberIdsIn(int|string $committee, bool $includeDescendants = true): array
    {
        return $this->idsIn($committee, $this->memberIds, $includeDescendants);
    }

    public function positionIdsIn(int|string $committee, bool $includeDescendants = true): array
    {
        return $this->idsIn($committee, $this->positionIds, $includeDescendants);
    }

    /**
     * A term id or a slug, resolved to a term id. A string is always a slug.
     */
    private function resolveId(int|string $committee): int
    {
        if (is_int($committee)) {
            return $committee > 0 && $this->findById($committee) !== null ? $committee : 0;
        }

        return $this->findBySlug($committee)?->getId() ?? 0;
    }

    /** @return array<int, Committee> */
    private function childrenOfId(int $parentId): array
    {
        $children = [];

        foreach ($this->committees as $committee) {
            if ($committee->getParentId() === $parentId) {
                $children[] = $committee;
            }
        }

        return $this->sorted($children);
    }

    /**
     * @param array<int, Committee> $found
     * @param array<int, true>      $seen
     */
    private function collectDescendants(int $parentId, array &$found, array $seen): void
    {
        if (isset($seen[$parentId])) {
            return;
        }

        $seen[$parentId] = true;

        foreach ($this->childrenOfId($parentId) as $child) {
            $found[] = $child;
            $this->collectDescendants($child->getId(), $found, $seen);
        }
    }

    /**
     * The committees one post is assigned to.
     *
     * @param array<string, array<int, int>> $map
     * @return array<int, Committee>
     */
    private function assignedTo(int $postId, array $map): array
    {
        $committees = [];

        foreach ($map as $slug => $ids) {
            if (!in_array($postId, $ids, true)) {
                continue;
            }

            $committee = $this->findBySlug((string) $slug);

            if ($committee !== null) {
                $committees[] = $committee;
            }
        }

        return $this->sorted($committees);
    }

    /**
     * @param array<string, array<int, int>> $map
     * @return array<int, int>
     */
    private function idsIn(int|string $committee, array $map, bool $includeDescendants): array
    {
        $resolved = $this->resolveId($committee);

        if ($resolved === 0) {
            return [];
        }

        $slugs = [$this->findById($resolved)?->getSlug() ?? ''];

        if ($includeDescendants) {
            foreach ($this->descendantsOf($resolved) as $descendant) {
                $slugs[] = $descendant->getSlug();
            }
        }

        $ids = [];
        foreach ($slugs as $slug) {
            foreach ($map[$slug] ?? [] as $id) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Ordered by name, as the real repository orders every listing.
     *
     * @param array<int, Committee> $committees
     * @return array<int, Committee>
     */
    private function sorted(array $committees): array
    {
        usort(
            $committees,
            static fn (Committee $a, Committee $b): int => strcasecmp($a->getName(), $b->getName())
        );

        return $committees;
    }
}
