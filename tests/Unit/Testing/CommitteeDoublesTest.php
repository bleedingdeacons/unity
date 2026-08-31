<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Testing;

use Unity\Committees\Interfaces\Committee;
use Unity\Committees\Interfaces\CommitteeRepository;
use Unity\Testing\Doubles\CommitteeStub;
use Unity\Testing\Doubles\InMemoryCommitteeRepository;
use Unity\Tests\TestCase;

/**
 * Tests for the committee test doubles.
 *
 * These ship for other plugins' suites, so what matters is that the hierarchy
 * they compute is the real one. A double that returned whatever it was handed
 * would let a consumer's test pass while the consumer walks the tree wrongly —
 * so the cases here are the walks: descendants, ancestors nearest-first, the
 * rollup, and a cycle, which wp-admin permits and which must not hang a suite.
 *
 * @covers \Unity\Testing\Doubles\CommitteeStub
 * @covers \Unity\Testing\Doubles\InMemoryCommitteeRepository
 */
final class CommitteeDoublesTest extends TestCase
{
    /**
     * Intergroup
     * ├── Public Information
     * │   └── Health
     * └── Telephones
     */
    private function repository(): InMemoryCommitteeRepository
    {
        return new InMemoryCommitteeRepository(
            [
                new CommitteeStub(1, 'intergroup', 'Intergroup'),
                new CommitteeStub(2, 'public-information', 'Public Information', 1),
                new CommitteeStub(3, 'pi-health', 'Health', 2),
                new CommitteeStub(4, 'telephones', 'Telephones', 1),
            ],
            [
                'intergroup'         => [10],
                'public-information' => [20, 21],
                'pi-health'          => [30],
                'telephones'         => [40],
            ],
            ['telephones' => [88]]
        );
    }

    /** @return array<int, string> */
    private function slugs(array $committees): array
    {
        return array_map(static fn (Committee $c): string => $c->getSlug(), $committees);
    }

    /** @test */
    public function the_stub_satisfies_the_contract(): void
    {
        $committee = new CommitteeStub(3, 'pi-health', 'Health', 2, 'Carrying the message');

        self::assertInstanceOf(Committee::class, $committee);
        self::assertSame(3, $committee->getId());
        self::assertSame('pi-health', $committee->getSlug());
        self::assertSame('Health', $committee->getName());
        self::assertSame('Carrying the message', $committee->getDescription());
        self::assertSame(2, $committee->getParentId());
        self::assertFalse($committee->isRoot());
        self::assertTrue((new CommitteeStub(1, 'intergroup', 'Intergroup'))->isRoot());
    }

    /** @test */
    public function the_repository_satisfies_the_contract(): void
    {
        self::assertInstanceOf(CommitteeRepository::class, $this->repository());
    }

    /** @test */
    public function it_finds_by_id_and_by_slug(): void
    {
        $repository = $this->repository();

        self::assertSame('pi-health', $repository->findById(3)?->getSlug());
        self::assertSame(3, $repository->findBySlug('pi-health')?->getId());
        self::assertNull($repository->findById(404));
        self::assertNull($repository->findBySlug('nope'));
        self::assertNull($repository->findBySlug(''));
    }

    /** @test */
    public function roots_are_the_committees_with_no_parent(): void
    {
        self::assertSame(['intergroup'], $this->slugs($this->repository()->roots()));
    }

    /** @test */
    public function children_are_one_level_and_descendants_are_the_branch(): void
    {
        $repository = $this->repository();

        self::assertSame(
            ['public-information', 'telephones'],
            $this->slugs($repository->childrenOf('intergroup'))
        );

        // Ordered by name, as the real repository orders every listing:
        // Health, Public Information, Telephones.
        self::assertSame(
            ['pi-health', 'public-information', 'telephones'],
            $this->slugs($repository->descendantsOf('intergroup'))
        );

        self::assertSame([], $repository->descendantsOf('pi-health'));
    }

    /** @test */
    public function ancestors_run_nearest_first_and_the_path_runs_root_first(): void
    {
        $repository = $this->repository();

        self::assertSame(
            ['public-information', 'intergroup'],
            $this->slugs($repository->ancestorsOf('pi-health'))
        );

        self::assertSame(
            ['intergroup', 'public-information', 'pi-health'],
            $this->slugs($repository->pathTo('pi-health'))
        );

        self::assertSame([], $repository->ancestorsOf('intergroup'));
    }

    /** @test */
    public function member_ids_roll_descendants_up_by_default(): void
    {
        $repository = $this->repository();

        $all = $repository->memberIdsIn('public-information');
        sort($all);
        self::assertSame([20, 21, 30], $all, 'Health rolls up into Public Information');

        self::assertSame([20, 21], $repository->memberIdsIn('public-information', false));
    }

    /** @test */
    public function a_member_in_two_committees_is_only_counted_once(): void
    {
        $repository = new InMemoryCommitteeRepository(
            [
                new CommitteeStub(1, 'intergroup', 'Intergroup'),
                new CommitteeStub(2, 'telephones', 'Telephones', 1),
            ],
            ['intergroup' => [10], 'telephones' => [10]]
        );

        self::assertSame([10], $repository->memberIdsIn('intergroup'));
    }

    /** @test */
    public function it_answers_which_committees_a_post_belongs_to(): void
    {
        $repository = $this->repository();

        self::assertSame(['public-information'], $this->slugs($repository->forMember(20)));
        self::assertSame(['telephones'], $this->slugs($repository->forPosition(88)));
        self::assertSame([], $repository->forMember(999));
    }

    /** @test */
    public function positions_are_kept_separate_from_members(): void
    {
        $repository = $this->repository();

        self::assertSame([88], $repository->positionIdsIn('telephones'));
        self::assertSame([40], $repository->memberIdsIn('telephones'));
    }

    /** @test */
    public function an_unknown_committee_is_empty_rather_than_an_error(): void
    {
        $repository = $this->repository();

        self::assertSame([], $repository->childrenOf('nope'));
        self::assertSame([], $repository->descendantsOf('nope'));
        self::assertSame([], $repository->ancestorsOf('nope'));
        self::assertSame([], $repository->pathTo('nope'));
        self::assertSame([], $repository->memberIdsIn('nope'));
        self::assertSame([], $repository->memberIdsIn(0));
    }

    /**
     * wp-admin lets a term hierarchy be edited into a loop. The real repository
     * mis-draws such a tree; a double that recursed forever would hang whatever
     * suite depends on it, which is a worse failure than the one it models.
     *
     * @test
     */
    public function a_cyclic_hierarchy_terminates(): void
    {
        $repository = new InMemoryCommitteeRepository(
            [
                new CommitteeStub(1, 'a', 'A', 2),
                new CommitteeStub(2, 'b', 'B', 1),
            ],
            ['a' => [10], 'b' => [11]]
        );

        self::assertNotEmpty($repository->descendantsOf('a'));
        self::assertNotEmpty($repository->ancestorsOf('a'));
        self::assertNotEmpty($repository->pathTo('a'));
        self::assertNotEmpty($repository->memberIdsIn('a'));
    }
}
