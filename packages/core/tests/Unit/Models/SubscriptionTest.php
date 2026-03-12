<?php

declare(strict_types=1);

use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Product;
use DayOne\Models\Subscription;
use DayOne\Models\UsageRecord;
use DayOne\Tests\Fixtures\Models\User;

it('casts status to SubscriptionStatus enum', function (): void {
    $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
    $user = User::create(['name' => 'User', 'email' => 'u@test.com', 'password' => 'pass']);

    $subscription = Subscription::withoutGlobalScopes()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'status' => 'active',
    ]);

    expect($subscription->status)->toBeInstanceOf(SubscriptionStatus::class);
    expect($subscription->status)->toBe(SubscriptionStatus::Active);
});

it('exposes isUsable property hook delegating to enum', function (): void {
    $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
    $user = User::create(['name' => 'User', 'email' => 'u@test.com', 'password' => 'pass']);

    $active = Subscription::withoutGlobalScopes()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $canceled = Subscription::withoutGlobalScopes()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'status' => SubscriptionStatus::Canceled,
    ]);

    expect($active->isUsable)->toBeTrue();
    expect($canceled->isUsable)->toBeFalse();
});

it('has user relationship', function (): void {
    $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
    $user = User::create(['name' => 'User', 'email' => 'u@test.com', 'password' => 'pass']);

    $subscription = Subscription::withoutGlobalScopes()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'status' => SubscriptionStatus::Active,
    ]);

    expect($subscription->user->id)->toBe($user->id);
});

it('has product relationship', function (): void {
    $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
    $user = User::create(['name' => 'User', 'email' => 'u@test.com', 'password' => 'pass']);

    $subscription = Subscription::withoutGlobalScopes()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'status' => SubscriptionStatus::Active,
    ]);

    expect($subscription->product->id)->toBe($product->id);
});

it('has usageRecords relationship', function (): void {
    $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
    $user = User::create(['name' => 'User', 'email' => 'u@test.com', 'password' => 'pass']);

    $subscription = Subscription::withoutGlobalScopes()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'status' => SubscriptionStatus::Active,
    ]);

    UsageRecord::create([
        'subscription_id' => $subscription->id,
        'feature' => 'api_calls',
        'quantity' => 10,
        'recorded_at' => now(),
    ]);

    expect($subscription->usageRecords)->toHaveCount(1);
    expect($subscription->usageRecords->first()->feature)->toBe('api_calls');
});
