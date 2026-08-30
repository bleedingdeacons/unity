<?php

declare(strict_types=1);

namespace Unity\Committees\Interfaces;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for Committee Repository
 *
 * Read-only by design. The committee tree is maintained by hand in wp-admin,
 * so there is no save/update/delete here — adding one would give code a second
 * way to change a structure that an administrator owns, and the two would
 * disagree the first time anyone used it.
 *
 * Every method that takes `$committee` accepts either a term ID or a slug.
 * Prefer the slug: term IDs are assigned per site, so the same committee has
 * different IDs on dev, test and production, and an ID written into code or
 * stored in an option is wrong the moment it leaves the machine it came from.
 *
 * An unknown committee is not an error. Lookups return null and listings
 * return an empty array, so a caller referencing a committee that a site has
 * not created yet degrades to "nobody" rather than fataling.
 */
interface CommitteeRepository
{
    /**
     * Find a committee by term ID
     *
     * @param int $id The committee's term ID
     * @return Committee|null The committee, or null if not found
     */
    public function findById(int $id): ?Committee;

    /**
     * Find a committee by slug
     *
     * @param string $slug The committee's slug
     * @return Committee|null The committee, or null if not found
     */
    public function findBySlug(string $slug): ?Committee;

    /**
     * Find every committee, at every level, ordered by name
     *
     * Flat, not nested — build a tree from {@see Committee::getParentId()} if
     * you need one. Committees with nothing assigned to them are included.
     *
     * @return array<int, Committee> Array of Committee objects
     */
    public function findAll(): array;

    /**
     * Find the committees at the top of the tree
     *
     * @return array<int, Committee> Committees with no parent, ordered by name
     */
    public function roots(): array;

    /**
     * Find a committee's direct children
     *
     * One level only. For the whole branch use {@see descendantsOf()}.
     *
     * @param int|string $committee A committee's term ID or slug
     * @return array<int, Committee> Ordered by name; empty if none or unknown
     */
    public function childrenOf(int|string $committee): array;

    /**
     * Find every committee below this one, at any depth
     *
     * @param int|string $committee A committee's term ID or slug
     * @return array<int, Committee> Ordered by name; empty if none or unknown
     */
    public function descendantsOf(int|string $committee): array;

    /**
     * Find a committee's ancestors, nearest first
     *
     * The committee's own parent comes first and the root last. Use
     * {@see pathTo()} for the reverse, including the committee itself.
     *
     * @param int|string $committee A committee's term ID or slug
     * @return array<int, Committee> Empty for a root committee or an unknown one
     */
    public function ancestorsOf(int|string $committee): array;

    /**
     * Find the full path from the root down to this committee, inclusive
     *
     * Suited to breadcrumbs and qualified labels — e.g. implode(' › ', …) over
     * the names gives "Public Information › Health".
     *
     * @param int|string $committee A committee's term ID or slug
     * @return array<int, Committee> Root first, the committee itself last;
     *                               empty when the committee is unknown
     */
    public function pathTo(int|string $committee): array;

    /**
     * Find the committees a member belongs to
     *
     * @param int $memberId The member's post ID
     * @return array<int, Committee> Ordered by name; empty if none, or if the
     *                               ID is not a member
     */
    public function forMember(int $memberId): array;

    /**
     * Find the committees a service position belongs to
     *
     * @param int $positionId The position's post ID
     * @return array<int, Committee> Ordered by name; empty if none, or if the
     *                               ID is not a position
     */
    public function forPosition(int $positionId): array;

    /**
     * Find the IDs of the members assigned to a committee
     *
     * Returns IDs rather than Member objects deliberately: hydrating them here
     * would make the committee layer depend on the member layer, and the
     * classification has no business knowing what it classifies. Callers that
     * want the models compose the two repositories —
     * `$members->findAll(['post__in' => $committees->memberIdsIn('telephones')])`
     * — which is also one query rather than one per member.
     *
     * @param int|string $committee          A committee's term ID or slug
     * @param bool       $includeDescendants Whether members of sub-committees
     *                                       count as members of this one.
     *                                       True by default: asking who is on
     *                                       Public Information almost always
     *                                       means Health and Employment too.
     * @return array<int, int> Member post IDs; empty if none or unknown
     */
    public function memberIdsIn(int|string $committee, bool $includeDescendants = true): array;

    /**
     * Find the IDs of the service positions assigned to a committee
     *
     * @param int|string $committee          A committee's term ID or slug
     * @param bool       $includeDescendants Whether positions of sub-committees
     *                                       count as positions of this one
     * @return array<int, int> Position post IDs; empty if none or unknown
     */
    public function positionIdsIn(int|string $committee, bool $includeDescendants = true): array;
}
