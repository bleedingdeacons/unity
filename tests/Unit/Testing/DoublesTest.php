<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Testing;

use Unity\Core\DependencyNotRegisteredException;
use Unity\Members\Interfaces\Member;
use Unity\Members\Interfaces\MemberRepository;
use Unity\Members\ResponderCertification;
use Unity\Testing\Doubles\FakeContainer;
use Unity\Testing\Doubles\InMemoryMemberRepository;
use Unity\Testing\Doubles\MemberStub;
use Unity\Tests\TestCase;

/**
 * The doubles Unity ships for the rest of the suite.
 *
 * PHP already enforces the contracts at class-load time — a method added to
 * Member fails this file before it fails any consumer, which is the reason
 * these live in Unity rather than in a separate test package. What is asserted
 * here is the behaviour consumers actually lean on and that a signature check
 * would not catch: that the container caches, that the repository filters
 * responders, that rejectWrites throws.
 *
 * @covers \Unity\Testing\Doubles\FakeContainer
 * @covers \Unity\Testing\Doubles\InMemoryMemberRepository
 * @covers \Unity\Testing\Doubles\MemberStub
 */
final class DoublesTest extends TestCase
{
    public function testMemberStubSatisfiesTheContractAndDefaultsEveryField(): void
    {
        $member = new MemberStub();

        self::assertInstanceOf(Member::class, $member);
        self::assertSame(0, $member->getId());
        self::assertSame('', $member->getAnonymousName());
        self::assertFalse($member->isTelephoneResponder());
        self::assertSame([], $member->getAccepts());
        self::assertSame(ResponderCertification::None, $member->getResponderCertification());
    }

    public function testMemberStubReturnsWhatItWasNamedWith(): void
    {
        $member = new MemberStub(
            id: 7,
            anonymousName: 'Alice B',
            personalEmail: 'alice@example.test',
            telephoneResponder: true,
            responderCertification: ResponderCertification::Certified,
            accepts: ['phone', 'email'],
        );

        self::assertSame(7, $member->getId());
        self::assertSame('Alice B', $member->getAnonymousName());
        self::assertSame('alice@example.test', $member->getPersonalEmail());
        self::assertTrue($member->isTelephoneResponder());
        self::assertSame(ResponderCertification::Certified, $member->getResponderCertification());
        self::assertSame(['phone', 'email'], $member->getAccepts());
    }

    public function testRepositoryReadsBackWhatItWasSeededWith(): void
    {
        $repository = new InMemoryMemberRepository([
            new MemberStub(id: 7, anonymousName: 'Alice B', personalEmail: 'alice@example.test'),
            new MemberStub(id: 8, anonymousName: 'Bob C'),
        ]);

        self::assertInstanceOf(MemberRepository::class, $repository);
        self::assertSame(2, $repository->count());
        self::assertSame('Alice B', $repository->findById(7)?->getAnonymousName());
        self::assertSame(7, $repository->findByEmail('alice@example.test')?->getId());
        self::assertNull($repository->findById(404));
        self::assertNull($repository->findByEmail('nobody@example.test'));
    }

    public function testRepositoryMatchesEmailWithoutRegardToCase(): void
    {
        // The real repository queries MySQL with '=' against a _ci collation,
        // so case does not matter there and must not matter here.
        $repository = new InMemoryMemberRepository([
            new MemberStub(id: 7, personalEmail: 'alice@example.test'),
        ]);

        self::assertSame(7, $repository->findByEmail('ALICE@EXAMPLE.TEST')?->getId());
        self::assertSame(7, $repository->findByEmail('Alice@Example.Test')?->getId());
    }

    public function testRepositoryFiltersTelephoneResponders(): void
    {
        $repository = new InMemoryMemberRepository([
            new MemberStub(id: 7, telephoneResponder: true),
            new MemberStub(id: 8, telephoneResponder: false),
        ]);

        // findAll() sees both; only the flagged one is a responder.
        self::assertCount(2, $repository->findAll());

        $responders = $repository->findTelephoneResponders();
        self::assertCount(1, $responders);
        self::assertSame(7, $responders[0]->getId());
    }

    public function testRepositoryRecordsWritesAndAppliesThem(): void
    {
        $repository = new InMemoryMemberRepository([new MemberStub(id: 7, anonymousName: 'Alice B')]);

        $id = $repository->create('Carol D');
        self::assertSame(8, $id);
        self::assertSame(['Carol D'], $repository->created);
        self::assertSame('Carol D', $repository->findById(8)?->getAnonymousName());

        $renamed = new MemberStub(id: 7, anonymousName: 'Alice Z');
        self::assertTrue($repository->update($renamed));
        self::assertSame([$renamed], $repository->updated);
        self::assertSame('Alice Z', $repository->findById(7)?->getAnonymousName());

        self::assertTrue($repository->delete(7));
        self::assertSame([7], $repository->deleted);
        self::assertNull($repository->findById(7));
    }

    public function testRepositoryRejectsWritesWhenAskedTo(): void
    {
        $repository = new InMemoryMemberRepository([new MemberStub(id: 7)], rejectWrites: true);

        $this->expectException(\LogicException::class);
        $repository->delete(7);
    }

    public function testContainerResolvesRegisteredFactoriesOnceAndCaches(): void
    {
        $container = new FakeContainer();
        $calls = 0;

        $container->register('service', function () use (&$calls): object {
            $calls++;

            return new \stdClass();
        });

        self::assertTrue($container->has('service'));
        self::assertSame(['service'], $container->registeredIds());

        $first = $container->get('service');
        self::assertSame($first, $container->get('service'));
        self::assertSame(1, $calls);
    }

    public function testContainerPrefersPresetsAndPassesItselfToFactories(): void
    {
        $seeded = new MemberStub(id: 7);
        $container = new FakeContainer([Member::class => $seeded]);
        $container->prime('answer', 42);

        $container->register('derived', static fn ($c): int => $c->get(Member::class)->getId());

        self::assertSame($seeded, $container->get(Member::class));
        self::assertSame(42, $container->get('answer'));
        self::assertSame(7, $container->get('derived'));
    }

    public function testContainerBuildRunsTheFactoryWithoutCaching(): void
    {
        $container = new FakeContainer();
        $container->register('service', static fn (): object => new \stdClass());

        self::assertNotSame($container->build('service'), $container->build('service'));
    }

    public function testContainerFallsBackToTheResolverForUnknownIds(): void
    {
        $container = new FakeContainer([], static fn (string $id): string => 'resolved:' . $id);

        self::assertSame('resolved:whatever', $container->get('whatever'));
    }

    public function testContainerThrowsTheRealExceptionForUnknownIdsWithoutAResolver(): void
    {
        $container = new FakeContainer();

        $this->expectException(DependencyNotRegisteredException::class);
        $container->get('missing');
    }
}
