<?php

declare(strict_types=1);

use DayOne\Adapters\Auth\SanctumAuthAdapter;
use DayOne\Contracts\Auth\V1\AuthManager;
use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Product;
use DayOne\Models\Subscription;
use DayOne\Runtime\ProductContextInstance;
use DayOne\Tests\Fixtures\Models\User;

beforeEach(function (): void {
    $this->productA = Product::create(['name' => 'Product A', 'slug' => 'product-a', 'is_active' => true]);
    $this->productB = Product::create(['name' => 'Product B', 'slug' => 'product-b', 'is_active' => true]);
    $this->userOne = User::create(['name' => 'User One', 'email' => 'one@test.com', 'password' => bcrypt('password')]);
    $this->userTwo = User::create(['name' => 'User Two', 'email' => 'two@test.com', 'password' => bcrypt('password')]);
});

it('creates product and registers user with access control', function (): void {
    /** @var AuthManager $auth */
    $auth = app(AuthManager::class);

    $auth->grantProductAccess(user: $this->userOne, product: $this->productA);

    expect($auth->hasProductAccess(user: $this->userOne, product: $this->productA))->toBeTrue()
        ->and($auth->hasProductAccess(user: $this->userTwo, product: $this->productA))->toBeFalse();
});

it('subscribes user to product with active status', function (): void {
    $subscription = Subscription::withoutGlobalScopes()->create([
        'user_id' => $this->userOne->id,
        'product_id' => $this->productA->id,
        'status' => SubscriptionStatus::Active,
    ]);

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->isUsable)->toBeTrue();
});

it('isolates subscriptions by product context', function (): void {
    Subscription::withoutGlobalScopes()->create([
        'user_id' => $this->userOne->id,
        'product_id' => $this->productA->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::withoutGlobalScopes()->create([
        'user_id' => $this->userTwo->id,
        'product_id' => $this->productB->id,
        'status' => SubscriptionStatus::Active,
    ]);

    /** @var ProductContextInstance $context */
    $context = app(ProductContextInstance::class);
    $context->setProduct($this->productA);

    $subscriptionsA = Subscription::all();
    expect($subscriptionsA)->toHaveCount(1)
        ->and($subscriptionsA->first()->product_id)->toBe($this->productA->id);

    $context->setProduct($this->productB);

    $subscriptionsB = Subscription::all();
    expect($subscriptionsB)->toHaveCount(1)
        ->and($subscriptionsB->first()->product_id)->toBe($this->productB->id);
});

it('scopes tokens by product context', function (): void {
    $contextA = new ProductContextInstance();
    $contextA->setProduct($this->productA);
    $adapterA = new SanctumAuthAdapter($contextA);

    $resultA = $adapterA->issueToken(user: $this->userOne, tokenName: 'token-a');
    $tokenA = $this->userOne->tokens()->where('id', $resultA->tokenId)->first();

    expect($tokenA->abilities)->toContain('product:product-a');

    $contextB = new ProductContextInstance();
    $contextB->setProduct($this->productB);
    $adapterB = new SanctumAuthAdapter($contextB);

    $resultB = $adapterB->issueToken(user: $this->userOne, tokenName: 'token-b');
    $tokenB = $this->userOne->tokens()->where('id', $resultB->tokenId)->first();

    expect($tokenB->abilities)->toContain('product:product-b')
        ->and($tokenB->abilities)->not->toContain('product:product-a');
});

it('manages product lifecycle states', function (): void {
    $product = Product::create(['name' => 'Lifecycle', 'slug' => 'lifecycle', 'is_active' => true]);

    expect($product->isActive)->toBeTrue();

    $product->update(['is_active' => false]);
    $product->refresh();
    expect($product->isActive)->toBeFalse();

    $product->update(['is_active' => true]);
    $product->refresh();
    expect($product->isActive)->toBeTrue();

    $product->update(['is_active' => false]);
    $product->refresh();
    expect($product->isActive)->toBeFalse();
});
