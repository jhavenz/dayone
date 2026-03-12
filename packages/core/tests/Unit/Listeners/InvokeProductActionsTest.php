<?php

declare(strict_types=1);

use DayOne\DTOs\SubscriptionStatus;
use DayOne\Events\SubscriptionCanceled;
use DayOne\Events\SubscriptionCreated;
use DayOne\Listeners\InvokeProductActions;
use DayOne\Models\Product;
use DayOne\Models\Subscription;
use DayOne\Tests\Fixtures\Models\User;

it('invokes configured closure action on matching event', function (): void {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-app', 'is_active' => true]);
    $user = User::create(['name' => 'User', 'email' => 'u@test.com', 'password' => 'pass']);

    $called = false;
    config()->set('dayone.products.test-app.actions.on_subscribe', function () use (&$called): void {
        $called = true;
    });

    $event = new SubscriptionCreated(
        user: $user,
        product: $product,
        subscriptionId: 'sub_123',
        status: SubscriptionStatus::Active,
    );

    $listener = new InvokeProductActions();
    $listener->handle($event);

    expect($called)->toBeTrue();
});

it('invokes configured class action on matching event', function (): void {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-app', 'is_active' => true]);
    $user = User::create(['name' => 'User', 'email' => 'u@test.com', 'password' => 'pass']);

    Subscription::withoutGlobalScopes()->create([
        'id' => 'sub_action_test',
        'user_id' => $user->id,
        'product_id' => $product->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $mockAction = new class {
        public static bool $handled = false;

        public function handle(object $event): void
        {
            static::$handled = true;
        }
    };

    app()->instance($mockAction::class, $mockAction);
    config()->set('dayone.products.test-app.actions.on_cancel', $mockAction::class);

    $event = new SubscriptionCanceled(
        subscriptionId: 'sub_action_test',
        product: $product,
    );

    $listener = new InvokeProductActions();
    $listener->handle($event);

    expect($mockAction::$handled)->toBeTrue();
});

it('does nothing when no action is configured', function (): void {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-app', 'is_active' => true]);
    $user = User::create(['name' => 'User', 'email' => 'u@test.com', 'password' => 'pass']);

    $event = new SubscriptionCreated(
        user: $user,
        product: $product,
        subscriptionId: 'sub_no_action',
        status: SubscriptionStatus::Active,
    );

    $listener = new InvokeProductActions();
    $listener->handle($event);

    expect(true)->toBeTrue();
});

it('does nothing when product cannot be resolved', function (): void {
    $event = new SubscriptionCanceled(
        subscriptionId: 'nonexistent_sub',
    );

    $listener = new InvokeProductActions();
    $listener->handle($event);

    expect(true)->toBeTrue();
});
