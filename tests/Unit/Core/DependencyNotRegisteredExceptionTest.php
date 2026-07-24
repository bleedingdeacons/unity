<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Core;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Unity\Core\DependencyNotRegisteredException;

/**
 * Tests for {@see DependencyNotRegisteredException} — the PSR-11
 * not-found exception the container throws for an unregistered id.
 */
class DependencyNotRegisteredExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_a_psr11_not_found_exception(): void
    {
        $e = new DependencyNotRegisteredException('Some\\Service');
        $this->assertInstanceOf(NotFoundExceptionInterface::class, $e);
    }

    /**
     * @test
     */
    public function it_carries_the_class_name_in_message_and_accessor(): void
    {
        $e = new DependencyNotRegisteredException('Some\\Service');

        $this->assertSame('Some\\Service', $e->getClassName());
        $this->assertStringContainsString('Some\\Service', $e->getMessage());
    }

    /**
     * @test
     */
    public function it_preserves_code_and_previous(): void
    {
        $previous = new Exception('root cause');
        $e = new DependencyNotRegisteredException('X', 42, $previous);

        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }
}
