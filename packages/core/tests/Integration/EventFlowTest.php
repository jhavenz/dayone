<?php

declare(strict_types=1);

use DayOne\Contracts\Events\V1\EventManager;
use DayOne\DTOs\SubscriptionStatus;
use DayOne\Events\ProductCreated;
use DayOne\Events\SubscriptionCreated;
use DayOne\Listeners\InvokeProductActions;
use DayOne\Models\Product;
use DayOne\Runtime\DayOneEventManager;
use DayOne\Tests\Fixtures\Models\User;
use Illuminate\Contracts\Events\Dispatcher;

it('flows billing events through the event manager', function (): void {
    $handled = false;

    $handler = new class {
        public static bool $handled = false;

        public function handle(object $event): void
        {
            static::$handled = true;
        }
    };

    $handler::$handled = false;
    app()->instance($handler::class, $handler);

    /** @var DayOneEventManager $manager */
    $manager = app(EventManager::class);
    $manager->registerProductListener('event-product', ProductCreated::class, $handler::class);

    $manager->dispatch(new ProductCreated(productSlug: 'event-product', productName: 'Event Product'));

    expect($handler::$handled)->toBeTrue();
});

it('fires product-scoped listeners only for their product', function (): void {
    $handlerA = new class {
        public static bool $handled = false;

        public function handle(object $event): void
        {
            static::$handled = true;
        }
    };

    $handlerB = new class {
        public static bool $handled = false;

        public function handle(object $event): void
        {
            static::$handled = true;
        }
    };

    $handlerA::$handled = false;
    $handlerB::$handled = false;

    app()->instance($handlerA::class, $handlerA);
    app()->instance($handlerB::class, $handlerB);

    /** @var DayOneEventManager $manager */
    $manager = app(EventManager::class);
    $manager->registerProductListener('prod-a', ProductCreated::class, $handlerA::class);
    $manager->registerProductListener('prod-b', ProductCreated::class, $handlerB::class);

    $manager->dispatch(new ProductCreated(productSlug: 'prod-a', productName: 'Prod A'));

    expect($handlerA::$handled)->toBeTrue()
        ->and($handlerB::$handled)->toBeFalse();
});

it('invokes configured product actions via InvokeProductActions', function (): void {
    $product = Product::create(['name' => 'Action Test', 'slug' => 'action-test', 'is_active' => true]);
    $user = User::create(['name' => 'Action User', 'email' => 'action@test.com', 'password' => bcrypt('password')]);

    $invoked = false;
    config()->set('dayone.products.action-test.actions.on_subscribe', function () use (&$invoked): void {
        $invoked = true;
    });

    $event = new SubscriptionCreated(
        user: $user,
        product: $product,
        subscriptionId: 'sub_action_flow',
        status: SubscriptionStatus::Active,
    );

    $listener = new InvokeProductActions();
    $listener->handle($event);

    expect($invoked)->toBeTrue();
});
