<?php

declare(strict_types=1);

namespace Unity\Testing\Doubles;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Unity\Meetings\Interfaces\Meeting;
use Unity\Meetings\Interfaces\MeetingRepository;

/**
 * An array-backed MeetingRepository for tests.
 *
 * Unlike the member, group and position doubles there is no write policy here,
 * because MeetingRepository is read-only — it has no save, update or delete.
 *
 * The finders do the filtering for real rather than answering every call with
 * the same set. That matters more here than for the other repositories: this
 * interface has six distinct finders, and a consumer's whole job may be
 * choosing between them, which a double that ignored the distinction would
 * make untestable.
 *
 * One relation cannot be derived from a Meeting: the interface exposes no
 * group id, so findByGroupId() is driven by a mapping the test supplies.
 * Passing none means no meeting belongs to any group, which is the honest
 * default — inventing a rule (every meeting belongs to group 1, say) would
 * make a consumer look correct against a relation that does not exist.
 */
final class InMemoryMeetingRepository implements MeetingRepository
{
    /** @var array<int, Meeting> */
    private array $meetings;

    /**
     * @param array<int, Meeting> $meetings
     * @param array<int, int> $groupByMeetingId Meeting id => group id, for
     *                                          findByGroupId(). Meetings
     *                                          absent from the map belong to
     *                                          no group.
     */
    public function __construct(array $meetings = [], private array $groupByMeetingId = [])
    {
        $this->meetings = array_values($meetings);
    }

    public function findById(int $id): ?Meeting
    {
        foreach ($this->meetings as $meeting) {
            if ($meeting->getId() === $id) {
                return $meeting;
            }
        }

        return null;
    }

    /**
     * Every meeting, or just the ids asked for.
     *
     * `post__in` is honoured for the same reason the member double honours it:
     * consumers narrow with it to hydrate a known set in one query —
     * CachingMeetingRepository re-reads evicted entries that way — and a
     * double that ignored the filter would let that look right in tests while
     * quietly handing back everybody. Order follows the ids given. Everything
     * else in $args is still ignored; this is a double, not a query engine.
     *
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findAll(array $args = []): array
    {
        if (!isset($args['post__in']) || !is_array($args['post__in'])) {
            return $this->meetings;
        }

        $wanted = array_map('intval', $args['post__in']);
        $byId = [];

        foreach ($this->meetings as $meeting) {
            $byId[$meeting->getId()] = $meeting;
        }

        $found = [];

        foreach ($wanted as $id) {
            if (isset($byId[$id])) {
                $found[] = $byId[$id];
            }
        }

        return $found;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findByDay(int $day, array $args = []): array
    {
        return $this->filter(static fn (Meeting $meeting): bool => $meeting->getDay() === $day);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findOnline(array $args = []): array
    {
        return $this->filter(static fn (Meeting $meeting): bool => $meeting->isOnline());
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findInPerson(array $args = []): array
    {
        return $this->filter(static fn (Meeting $meeting): bool => !$meeting->isOnline());
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findByGroupId(int $groupId, array $args = []): array
    {
        $map = $this->groupByMeetingId;

        return $this->filter(
            static fn (Meeting $meeting): bool => ($map[$meeting->getId()] ?? null) === $groupId
        );
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function findByLocationId(int $locationId, array $args = []): array
    {
        // Derivable, unlike the group relation: a Meeting carries its Location.
        return $this->filter(static function (Meeting $meeting) use ($locationId): bool {
            $location = $meeting->getLocation();

            return $location !== null && $location->getId() === $locationId;
        });
    }

    /**
     * Case-insensitive substring match on the meeting name.
     *
     * The real implementation searches more widely; a double that tried to
     * reproduce that would be guessing, and consumers assert on which meetings
     * came back rather than on how the match was made.
     *
     * @param array<string, mixed> $args
     * @return array<int, Meeting>
     */
    public function search(string $keyword, array $args = []): array
    {
        $needle = strtolower($keyword);

        return $this->filter(
            static fn (Meeting $meeting): bool => str_contains(strtolower($meeting->getName()), $needle)
        );
    }

    /**
     * @param array<string, mixed> $args
     */
    public function count(array $args = []): int
    {
        return count($this->meetings);
    }

    /**
     * @param callable(Meeting): bool $predicate
     * @return array<int, Meeting>
     */
    private function filter(callable $predicate): array
    {
        return array_values(array_filter($this->meetings, $predicate));
    }
}
