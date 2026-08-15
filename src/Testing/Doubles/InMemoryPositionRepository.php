<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use LogicException;
use Unity\Positions\Interfaces\Position;
use Unity\Positions\Interfaces\PositionRepository;

/**
 * An array-backed PositionRepository for tests.
 *
 * Same write policy as {@see InMemoryMemberRepository}: mutations are recorded
 * and reported successful by default, or throw when rejectWrites is set.
 */
final class InMemoryPositionRepository implements PositionRepository
{
    /** @var array<int, Position> */
    private array $positions;

    /**
     * Positions passed to save(), in order.
     *
     * @var array<int, Position>
     */
    public array $saved = [];

    /**
     * Positions passed to update(), in order.
     *
     * @var array<int, Position>
     */
    public array $updated = [];

    /**
     * Ids passed to delete(), in order.
     *
     * @var array<int, int>
     */
    public array $deleted = [];

    /**
     * @param array<int, Position> $positions
     * @param bool $rejectWrites Throw from every mutation instead of recording it.
     */
    public function __construct(array $positions = [], private bool $rejectWrites = false)
    {
        $this->positions = array_values($positions);
    }

    public function findById(int $id): ?Position
    {
        foreach ($this->positions as $position) {
            if ($position->getId() === $id) {
                return $position;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $args Accepted and ignored.
     * @return array<int, Position>
     */
    public function findAll(array $args = []): array
    {
        return $this->positions;
    }

    /**
     * @param array<string, mixed> $args
     */
    public function count(array $args = []): int
    {
        return count($this->positions);
    }

    public function save(Position $position): bool
    {
        if ($this->rejectWrites) {
            throw new LogicException('InMemoryPositionRepository::save() called on a read-only double.');
        }

        $this->saved[] = $position;

        return true;
    }

    public function update(Position $position): bool
    {
        if ($this->rejectWrites) {
            throw new LogicException('InMemoryPositionRepository::update() called on a read-only double.');
        }

        $this->updated[] = $position;

        return true;
    }

    public function delete(int $id): bool
    {
        if ($this->rejectWrites) {
            throw new LogicException('InMemoryPositionRepository::delete() called on a read-only double.');
        }

        $this->deleted[] = $id;
        $this->positions = array_values(array_filter(
            $this->positions,
            static fn (Position $position): bool => $position->getId() !== $id
        ));

        return true;
    }
}
