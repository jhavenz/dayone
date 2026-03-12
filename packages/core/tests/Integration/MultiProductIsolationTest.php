<?php

declare(strict_types=1);

use DayOne\Contracts\Auth\V1\AuthManager;
use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Product;
use DayOne\Models\Subscription;
use DayOne\Runtime\ProductContextInstance;
use DayOne\Tests\Fixtures\Models\User;

beforeEach(function (): void {
    $this->productA = Product::create(['name' => 'Product A', 'slug' => 'product-a', 'is_active' => true]);
    $this->productB = Product::create(['name' => 'Product B', 'slug' => 'product-b', 'is_active' => true]);
    $this->user = User::create(['name' => 'Test User', 'email' => 'user@test.com', 'password' => bcrypt('password')]);
});

it('scopes BelongsToProduct queries to active context', function (): void {
    Subscription::withoutGlobalScopes()->create([
        'user_id' => $this->user->id,
        'product_id' => $this->productA->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::withoutGlobalScopes()->create([
        'user_id' => $this->user->id,
        'product_id' => $this->productB->id,
        'status' => SubscriptionStatus::Trialing,
    ]);

    /** @var ProductContextInstance $context */
    $context = app(ProductContextInstance::class);

    $context->setProduct($this->productA);
    $subsA = Subscription::all();
    expect($subsA)->toHaveCount(1)
        ->and($subsA->first()->status)->toBe(SubscriptionStatus::Active);

    $context->setProduct($this->productB);
    $subsB = Subscription::all();
    expect($subsB)->toHaveCount(1)
        ->and($subsB->first()->status)->toBe(SubscriptionStatus::Trialing);
});

it('allows user to belong to multiple products', function (): void {
    /** @var AuthManager $auth */
    $auth = app(AuthManager::class);

    $auth->grantProductAccess(user: $this->user, product: $this->productA);
    $auth->grantProductAccess(user: $this->user, product: $this->productB);

    expect($auth->hasProductAccess(user: $this->user, product: $this->productA))->toBeTrue()
        ->and($auth->hasProductAccess(user: $this->user, product: $this->productB))->toBeTrue();

    $auth->revokeProductAccess(user: $this->user, product: $this->productA);

    expect($auth->hasProductAccess(user: $this->user, product: $this->productA))->toBeFalse()
        ->and($auth->hasProductAccess(user: $this->user, product: $this->productB))->toBeTrue();
});

it('prevents subscription leakage between products', function (): void {
    $userTwo = User::create(['name' => 'User Two', 'email' => 'two@test.com', 'password' => bcrypt('password')]);

    Subscription::withoutGlobalScopes()->create([
        'user_id' => $this->user->id,
        'product_id' => $this->productA->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::withoutGlobalScopes()->create([
        'user_id' => $userTwo->id,
        'product_id' => $this->productA->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::withoutGlobalScopes()->create([
        'user_id' => $this->user->id,
        'product_id' => $this->productB->id,
        'status' => SubscriptionStatus::Active,
    ]);

    /** @var ProductContextInstance $context */
    $context = app(ProductContextInstance::class);

    $context->setProduct($this->productA);
    $subsA = Subscription::all();
    expect($subsA)->toHaveCount(2);
    $subsA->each(function (Subscription $sub): void {
        expect($sub->product_id)->toBe($this->productA->id);
    });

    $context->setProduct($this->productB);
    $subsB = Subscription::all();
    expect($subsB)->toHaveCount(1)
        ->and($subsB->first()->product_id)->toBe($this->productB->id);
});
