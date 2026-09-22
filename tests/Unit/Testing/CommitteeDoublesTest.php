<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Testing;

use Unity\Committees\Interfaces\Committee;
use Unity\Committees\Interfaces\CommitteeRepository;
use Unity\Testing\Doubles\CommitteeStub;
use Unity\Testing\Doubles\InMemoryCommitteeRepository;

/*
 * Tests for the committee test doubles.
 *
 * These ship for other plugins' suites, so what matters is that the hierarchy
 * they compute is the real one. A double that returned whatever it was handed
 * would let a consumer's test pass while the consumer walks the tree wrongly —
 * so the cases here are the walks: descendants, ancestors nearest-first, the
 * rollup, and a cycle, which wp-admin permits and which must not hang a suite.
 */

// No covers(): src/Testing is excluded from coverage in phpunit.xml, so naming
// those classes as covered targets attributes nothing and PHPUnit 13 rejects
// them outright. The @covers these replace had the same problem; it was
// simply never validated. The PHPUnit class carried #[CoversNothing]; Pest has
// no coversNothing(), so these tests now attribute whatever non-excluded
// source they happen to run.

/**
 * Intergroup
 * ├── Public Information
 * │   └── Health
 * └── Telephones
 */
function committeeRepository(): InMemoryCommitteeRepository
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

/**
 * @param array<int, Committee> $committees
 * @return array<int, string>
 */
function committeeSlugs(array $committees): array
{
    return array_map(static fn (Committee $c): string => $c->getSlug(), $committees);
}

it('has a stub that satisfies the contract', function () {
    $committee = new CommitteeStub(3, 'pi-health', 'Health', 2, 'Carrying the message');

    expect($committee)->toBeInstanceOf(Committee::class)
        ->and($committee->getId())->toBe(3)
        ->and($committee->getSlug())->toBe('pi-health')
        ->and($committee->getName())->toBe('Health')
        ->and($committee->getDescription())->toBe('Carrying the message')
        ->and($committee->getParentId())->toBe(2)
        ->and($committee->isRoot())->toBeFalse()
        ->and((new CommitteeStub(1, 'intergroup', 'Intergroup'))->isRoot())->toBeTrue();
});

it('has a repository that satisfies the contract', function () {
    expect(committeeRepository())->toBeInstanceOf(CommitteeRepository::class);
});

it('finds by id and by slug', function () {
    $repository = committeeRepository();

    expect($repository->findById(3)?->getSlug())->toBe('pi-health')
        ->and($repository->findBySlug('pi-health')?->getId())->toBe(3)
        ->and($repository->findById(404))->toBeNull()
        ->and($repository->findBySlug('nope'))->toBeNull()
        ->and($repository->findBySlug(''))->toBeNull();
});

it('treats the committees with no parent as roots', function () {
    expect(committeeSlugs(committeeRepository()->roots()))->toBe(['intergroup']);
});

it('keeps children to one level and descendants to the branch', function () {
    $repository = committeeRepository();

    expect(committeeSlugs($repository->childrenOf('intergroup')))
        ->toBe(['public-information', 'telephones']);

    // Ordered by name, as the real repository orders every listing:
    // Health, Public Information, Telephones.
    expect(committeeSlugs($repository->descendantsOf('intergroup')))
        ->toBe(['pi-health', 'public-information', 'telephones']);

    expect($repository->descendantsOf('pi-health'))->toBe([]);
});

it('runs ancestors nearest first and the path root first', function () {
    $repository = committeeRepository();

    expect(committeeSlugs($repository->ancestorsOf('pi-health')))
        ->toBe(['public-information', 'intergroup'])
        ->and(committeeSlugs($repository->pathTo('pi-health')))
        ->toBe(['intergroup', 'public-information', 'pi-health'])
        ->and($repository->ancestorsOf('intergroup'))->toBe([]);
});

it('rolls descendants up into member ids by default', function () {
    $repository = committeeRepository();

    $all = $repository->memberIdsIn('public-information');
    sort($all);
    expect($all)->toBe([20, 21, 30], 'Health rolls up into Public Information')
        ->and($repository->memberIdsIn('public-information', false))->toBe([20, 21]);
});

it('counts a member in two committees only once', function () {
    $repository = new InMemoryCommitteeRepository(
        [
            new CommitteeStub(1, 'intergroup', 'Intergroup'),
            new CommitteeStub(2, 'telephones', 'Telephones', 1),
        ],
        ['intergroup' => [10], 'telephones' => [10]]
    );

    expect($repository->memberIdsIn('intergroup'))->toBe([10]);
});

it('answers which committees a post belongs to', function () {
    $repository = committeeRepository();

    expect(committeeSlugs($repository->forMember(20)))->toBe(['public-information'])
        ->and(committeeSlugs($repository->forPosition(88)))->toBe(['telephones'])
        ->and($repository->forMember(999))->toBe([]);
});

it('keeps positions separate from members', function () {
    $repository = committeeRepository();

    expect($repository->positionIdsIn('telephones'))->toBe([88])
        ->and($repository->memberIdsIn('telephones'))->toBe([40]);
});

it('treats an unknown committee as empty rather than an error', function () {
    $repository = committeeRepository();

    expect($repository->childrenOf('nope'))->toBe([])
        ->and($repository->descendantsOf('nope'))->toBe([])
        ->and($repository->ancestorsOf('nope'))->toBe([])
        ->and($repository->pathTo('nope'))->toBe([])
        ->and($repository->memberIdsIn('nope'))->toBe([])
        ->and($repository->memberIdsIn(0))->toBe([]);
});

// wp-admin lets a term hierarchy be edited into a loop. The real repository
// mis-draws such a tree; a double that recursed forever would hang whatever
// suite depends on it, which is a worse failure than the one it models.
it('terminates on a cyclic hierarchy', function () {
    $repository = new InMemoryCommitteeRepository(
        [
            new CommitteeStub(1, 'a', 'A', 2),
            new CommitteeStub(2, 'b', 'B', 1),
        ],
        ['a' => [10], 'b' => [11]]
    );

    expect($repository->descendantsOf('a'))->not->toBeEmpty()
        ->and($repository->ancestorsOf('a'))->not->toBeEmpty()
        ->and($repository->pathTo('a'))->not->toBeEmpty()
        ->and($repository->memberIdsIn('a'))->not->toBeEmpty();
});
