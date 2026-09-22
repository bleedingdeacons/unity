<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Testing;

use Unity\Core\DependencyNotRegisteredException;
use Unity\Members\Interfaces\Member;
use Unity\Members\Interfaces\MemberRepository;
use Unity\Members\ResponderCertification;
use Unity\Testing\Doubles\FakeContainer;
use Unity\Testing\Doubles\InMemoryCache;
use Unity\Testing\Doubles\InMemoryMemberRepository;
use Unity\Testing\Doubles\MemberStub;

/*
 * The doubles Unity ships for the rest of the suite.
 *
 * PHP already enforces the contracts at class-load time — a method added to
 * Member fails this file before it fails any consumer, which is the reason
 * these live in Unity rather than in a separate test package. What is asserted
 * here is the behaviour consumers actually lean on and that a signature check
 * would not catch: that the container caches, that the repository filters
 * responders, that rejectWrites throws.
 */

// No covers(): src/Testing is excluded from coverage in phpunit.xml, so naming
// those classes as covered targets attributes nothing and PHPUnit 13 rejects
// them outright. The @covers these replace had the same problem; it was
// simply never validated. The PHPUnit class carried #[CoversNothing]; Pest has
// no coversNothing(), so these tests now attribute whatever non-excluded
// source they happen to run.

describe('MemberStub', function () {
    it('satisfies the contract and defaults every field', function () {
        $member = new MemberStub();

        expect($member)->toBeInstanceOf(Member::class)
            ->and($member->getId())->toBe(0)
            ->and($member->getAnonymousName())->toBe('')
            ->and($member->isTelephoneResponder())->toBeFalse()
            ->and($member->getAccepts())->toBe([])
            ->and($member->getResponderCertification())->toBe(ResponderCertification::None);
    });

    it('returns what it was named with', function () {
        $member = new MemberStub(
            id: 7,
            anonymousName: 'Alice B',
            personalEmail: 'alice@example.test',
            telephoneResponder: true,
            responderCertification: ResponderCertification::Certified,
            accepts: ['phone', 'email'],
        );

        expect($member->getId())->toBe(7)
            ->and($member->getAnonymousName())->toBe('Alice B')
            ->and($member->getPersonalEmail())->toBe('alice@example.test')
            ->and($member->isTelephoneResponder())->toBeTrue()
            ->and($member->getResponderCertification())->toBe(ResponderCertification::Certified)
            ->and($member->getAccepts())->toBe(['phone', 'email']);
    });
});

describe('InMemoryMemberRepository', function () {
    it('reads back what it was seeded with', function () {
        $repository = new InMemoryMemberRepository([
            new MemberStub(id: 7, anonymousName: 'Alice B', personalEmail: 'alice@example.test'),
            new MemberStub(id: 8, anonymousName: 'Bob C'),
        ]);

        expect($repository)->toBeInstanceOf(MemberRepository::class)
            ->and($repository->count())->toBe(2)
            ->and($repository->findById(7)?->getAnonymousName())->toBe('Alice B')
            ->and($repository->findByEmail('alice@example.test')?->getId())->toBe(7)
            ->and($repository->findById(404))->toBeNull()
            ->and($repository->findByEmail('nobody@example.test'))->toBeNull();
    });

    it('matches email without regard to case', function () {
        // The real repository queries MySQL with '=' against a _ci collation,
        // so case does not matter there and must not matter here.
        $repository = new InMemoryMemberRepository([
            new MemberStub(id: 7, personalEmail: 'alice@example.test'),
        ]);

        expect($repository->findByEmail('ALICE@EXAMPLE.TEST')?->getId())->toBe(7)
            ->and($repository->findByEmail('Alice@Example.Test')?->getId())->toBe(7);
    });

    it('filters telephone responders', function () {
        $repository = new InMemoryMemberRepository([
            new MemberStub(id: 7, telephoneResponder: true),
            new MemberStub(id: 8, telephoneResponder: false),
        ]);

        // findAll() sees both; only the flagged one is a responder.
        expect($repository->findAll())->toHaveCount(2);

        $responders = $repository->findTelephoneResponders();
        expect($responders)->toHaveCount(1)
            ->and($responders[0]->getId())->toBe(7);
    });

    // Consumers hydrate a set of ids in one call rather than one at a time —
    // Reach's committee messaging does — and a double that ignored the filter
    // would let that look right in tests while handing back everybody.
    it('narrows to post__in', function () {
        $repository = new InMemoryMemberRepository([
            new MemberStub(id: 7),
            new MemberStub(id: 8),
            new MemberStub(id: 9),
        ]);

        $found = $repository->findAll(['post__in' => [9, 7]]);

        // Ordered by the ids given, not by insertion.
        expect(array_map(
            static fn ($member): int => $member->getId(),
            $found
        ))->toBe([9, 7]);

        // An id nobody holds is simply absent, not an error.
        expect($repository->findAll(['post__in' => [7, 404]]))->toHaveCount(1);

        // Anything else in $args is still ignored: this is a double, not a
        // query engine.
        expect($repository->findAll(['orderby' => 'title']))->toHaveCount(3)
            ->and($repository->findAll())->toHaveCount(3);
    });

    it('records writes and applies them', function () {
        $repository = new InMemoryMemberRepository([new MemberStub(id: 7, anonymousName: 'Alice B')]);

        $id = $repository->create('Carol D');
        expect($id)->toBe(8)
            ->and($repository->created)->toBe(['Carol D'])
            ->and($repository->findById(8)?->getAnonymousName())->toBe('Carol D');

        $renamed = new MemberStub(id: 7, anonymousName: 'Alice Z');
        expect($repository->update($renamed))->toBeTrue()
            ->and($repository->updated)->toBe([$renamed])
            ->and($repository->findById(7)?->getAnonymousName())->toBe('Alice Z');

        expect($repository->delete(7))->toBeTrue()
            ->and($repository->deleted)->toBe([7])
            ->and($repository->findById(7))->toBeNull();
    });

    it('rejects writes when asked to', function () {
        $repository = new InMemoryMemberRepository([new MemberStub(id: 7)], rejectWrites: true);

        $repository->delete(7);
    })->throws(\LogicException::class);
});

describe('FakeContainer', function () {
    it('resolves registered factories once and caches', function () {
        $container = new FakeContainer();
        $calls = 0;

        $container->register('service', function () use (&$calls): object {
            $calls++;

            return new \stdClass();
        });

        expect($container->has('service'))->toBeTrue()
            ->and($container->registeredIds())->toBe(['service']);

        $first = $container->get('service');
        expect($container->get('service'))->toBe($first)
            ->and($calls)->toBe(1);
    });

    it('prefers presets and passes itself to factories', function () {
        $seeded = new MemberStub(id: 7);
        $container = new FakeContainer([Member::class => $seeded]);
        $container->prime('answer', 42);

        $container->register('derived', static fn ($c): int => $c->get(Member::class)->getId());

        expect($container->get(Member::class))->toBe($seeded)
            ->and($container->get('answer'))->toBe(42)
            ->and($container->get('derived'))->toBe(7);
    });

    it('runs the factory without caching on build', function () {
        $container = new FakeContainer();
        $container->register('service', static fn (): object => new \stdClass());

        expect($container->build('service'))->not->toBe($container->build('service'));
    });

    it('falls back to the resolver for unknown ids', function () {
        $container = new FakeContainer([], static fn (string $id): string => 'resolved:' . $id);

        expect($container->get('whatever'))->toBe('resolved:whatever');
    });

    it('throws the real exception for unknown ids without a resolver', function () {
        $container = new FakeContainer();

        $container->get('missing');
    })->throws(DependencyNotRegisteredException::class);
});

describe('InMemoryCache', function () {
    it('answers false for a miss and the value for a hit', function () {
        $cache = new InMemoryCache();

        // False rather than null, because that is what wp_cache_get() answers
        // and what consumers branch on.
        expect($cache->get('absent', 'unity_members'))->toBeFalse();

        $cache->set('member_1', ['id' => 1], 'unity_members', 43200);

        expect($cache->get('member_1', 'unity_members'))->toBe(['id' => 1])
            ->and($cache->expiries['unity_members/member_1'])->toBe(43200);
    });

    it('answers a multi-get for every key and counts the round trip', function () {
        $cache = new InMemoryCache();
        $cache->set('member_1', 'Alice', 'unity_members');

        $found = $cache->getMultiple(['member_1', 'member_2'], 'unity_members');

        expect($found)->toBe(['member_1' => 'Alice', 'member_2' => false])
            ->and($cache->multiGets)->toBe(1)
            ->and($cache->reads)->toBe(['unity_members/member_1', 'unity_members/member_2']);
    });

    it('keeps groups apart', function () {
        $cache = new InMemoryCache();
        $cache->set('same', 'members', 'unity_members');
        $cache->set('same', 'meetings', 'unity_meetings');

        expect($cache->get('same', 'unity_members'))->toBe('members')
            ->and($cache->get('same', 'unity_meetings'))->toBe('meetings');
    });

    it('leaves no trace on eviction, and deleting what is gone fails', function () {
        $cache = new InMemoryCache();
        $cache->set('member_1', 'Alice', 'unity_members');

        $cache->evict('member_1', 'unity_members');

        expect($cache->get('member_1', 'unity_members'))->toBeFalse()
            ->and($cache->writes)->toBe(['unity_members/member_1'])
            ->and($cache->delete('member_1', 'unity_members'))->toBeFalse();
    });

    it('empties every group on flush', function () {
        $cache = new InMemoryCache();
        $cache->set('a', 1, 'unity_members');
        $cache->set('b', 2, 'unity_meetings');

        $cache->flush();

        expect($cache->get('a', 'unity_members'))->toBeFalse()
            ->and($cache->get('b', 'unity_meetings'))->toBeFalse();
    });
});
