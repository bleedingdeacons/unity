<?php

declare(strict_types=1);

namespace Unity\Tests\Unit\Core;

use Exception;
use Psr\Container\NotFoundExceptionInterface;
use Unity\Core\DependencyNotRegisteredException;

/*
 * Tests for {@see DependencyNotRegisteredException} — the PSR-11
 * not-found exception the container throws for an unregistered id.
 */

it('is a PSR-11 not-found exception', function () {
    $e = new DependencyNotRegisteredException('Some\\Service');
    expect($e)->toBeInstanceOf(NotFoundExceptionInterface::class);
});

it('carries the class name in the message and the accessor', function () {
    $e = new DependencyNotRegisteredException('Some\\Service');

    expect($e->getClassName())->toBe('Some\\Service')
        ->and($e->getMessage())->toContain('Some\\Service');
});

it('preserves the code and the previous exception', function () {
    $previous = new Exception('root cause');
    $e = new DependencyNotRegisteredException('X', 42, $previous);

    expect($e->getCode())->toBe(42)
        ->and($e->getPrevious())->toBe($previous);
});
