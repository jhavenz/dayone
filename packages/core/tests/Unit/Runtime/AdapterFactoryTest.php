<?php

declare(strict_types=1);

use DayOne\Runtime\AdapterFactory;

final class StubAdapter
{
    public string $name = '';
    public bool $initialized = false;

    public function __construct()
    {
        $this->initialized = true;
    }
}

it('creates a lazy ghost that defers initialization until property access', function (): void {
    $factory = new AdapterFactory();
    $initializerCalled = false;

    $ghost = $factory->createLazy(StubAdapter::class, function (StubAdapter $instance) use (&$initializerCalled): void {
        $initializerCalled = true;
        $instance->name = 'initialized';
    });

    expect($ghost)->toBeInstanceOf(StubAdapter::class)
        ->and($initializerCalled)->toBeFalse();

    $name = $ghost->name;

    expect($initializerCalled)->toBeTrue()
        ->and($name)->toBe('initialized');
});

it('produces a lazy ghost that behaves like a real instance after initialization', function (): void {
    $factory = new AdapterFactory();

    $ghost = $factory->createLazy(StubAdapter::class, function (StubAdapter $instance): void {
        $instance->name = 'real';
        $instance->initialized = true;
    });

    expect($ghost->name)->toBe('real')
        ->and($ghost->initialized)->toBeTrue()
        ->and($ghost)->toBeInstanceOf(StubAdapter::class);
});
