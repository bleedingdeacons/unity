<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Committees\Interfaces\Committee;

/**
 * An inert Committee value object for tests.
 *
 * A committee is a taxonomy term rather than a post, so this is the only
 * entity stub with no `updated` -- terms have no modified date. Its parent is
 * an id, not another stub: the hierarchy is assembled by
 * {@see InMemoryCommitteeRepository}, so a stub handed round on its own is
 * never a half-built branch.
 *
 * @phpstan-consistent-constructor
 */
class CommitteeStub implements Committee
{
    public function __construct(
        private int $id = 0,
        private string $slug = '',
        private string $name = '',
        private int $parentId = 0,
        private string $description = ''
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function isRoot(): bool
    {
        return $this->parentId === 0;
    }
}
