<?php

declare(strict_types=1);

use DayOne\Contracts\Events\V1\EventManager;
use DayOne\Events\DayOneEvent;
use DayOne\Events\ProductCreated;
use DayOne\Runtime\DayOneEventManager;
use Illuminate\Contracts\Events\Dispatcher;

it('dispatches event through Laravel dispatcher', function (): void {
    $dispatched = false;

    $dispatcher = app(Dispatcher::class);
    $dispatcher->listen(ProductCreated::class, function () use (&$dispatched): void {
        $dispatched = true;
    });

    $manager = new DayOneEventManager($dispatcher);
    $manager->dispatch(new ProductCreated(productSlug: 'test', productName: 'Test'));

    expect($dispatched)->toBeTrue();
});

it('fires product-scoped event name for events with productSlug', function (): void {
    $scopedFired = false;

    $dispatcher = app(Dispatcher::class);
    $dispatcher->listen('dayone.test-app.ProductCreated', function () use (&$scopedFired): void {
        $scopedFired = true;
    });

    $manager = new DayOneEventManager($dispatcher);
    $manager->dispatch(new ProductCreated(productSlug: 'test-app', productName: 'Test App'));

    expect($scopedFired)->toBeTrue();
});

it('does not fire product-scoped event when productSlug is null', function (): void {
    $scopedFired = false;

    $dispatcher = app(Dispatcher::class);
    $dispatcher->listen('dayone..AnonymousEvent', function () use (&$scopedFired): void {
        $scopedFired = true;
    });

    $event = new class () {
        public ?string $productSlug = null;
    };

    $manager = new DayOneEventManager($dispatcher);
    $manager->dispatch($event);

    expect($scopedFired)->toBeFalse();
});

it('registerProductListener registers listener that filters by product', function (): void {
    $handled = false;

    $handler = new class {
        public static bool $handled = false;

        public function handle(object $event): void
        {
            static::$handled = true;
        }
    };

    app()->instance($handler::class, $handler);

    $dispatcher = app(Dispatcher::class);
    $manager = new DayOneEventManager($dispatcher);
    $manager->registerProductListener('my-product', ProductCreated::class, $handler::class);

    $manager->dispatch(new ProductCreated(productSlug: 'my-product', productName: 'My Product'));

    expect($handler::$handled)->toBeTrue();
});

it('product-scoped listener does NOT fire for different product', function (): void {
    $handler = new class {
        public static bool $handled = false;

        public function handle(object $event): void
        {
            static::$handled = true;
        }
    };

    $handler::$handled = false;
    app()->instance($handler::class, $handler);

    $dispatcher = app(Dispatcher::class);
    $manager = new DayOneEventManager($dispatcher);
    $manager->registerProductListener('product-a', ProductCreated::class, $handler::class);

    $manager->dispatch(new ProductCreated(productSlug: 'product-b', productName: 'Product B'));

    expect($handler::$handled)->toBeFalse();
});

it('getProductListeners returns registered listeners', function (): void {
    $dispatcher = app(Dispatcher::class);
    $manager = new DayOneEventManager($dispatcher);

    $manager->registerProductListener('my-product', ProductCreated::class, 'App\\Listeners\\OnCreated');
    $manager->registerProductListener('my-product', ProductCreated::class, 'App\\Listeners\\AnotherCreated');

    $listeners = $manager->getProductListeners('my-product');

    expect($listeners)->toHaveKey(ProductCreated::class)
        ->and($listeners[ProductCreated::class])->toBe([
            'App\\Listeners\\OnCreated',
            'App\\Listeners\\AnotherCreated',
        ]);
});

it('getProductListeners returns empty array for unknown product', function (): void {
    $dispatcher = app(Dispatcher::class);
    $manager = new DayOneEventManager($dispatcher);

    expect($manager->getProductListeners('unknown'))->toBe([]);
});

it('is bound as EventManager contract in the container', function (): void {
    $manager = app(EventManager::class);

    expect($manager)->toBeInstanceOf(DayOneEventManager::class);
});
