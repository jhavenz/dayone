<?php

declare(strict_types=1);

use DayOne\Adapters\Auth\SanctumAuthAdapter;
use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\DTOs\TokenResult;
use DayOne\Events\ProductAccessGranted;
use DayOne\Events\ProductAccessRevoked;
use DayOne\Events\TokenIssued;
use DayOne\Events\TokenRevoked;
use DayOne\Models\Product;
use DayOne\Runtime\ProductContextInstance;
use DayOne\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    if (! Schema::hasTable('users')) {
        Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }
});

function createTestUser(): User
{
    return User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
}

function createTestProduct(): Product
{
    return Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'is_active' => true,
    ]);
}

function makeAdapter(?ProductContextInstance $context = null): SanctumAuthAdapter
{
    $ctx = $context ?? app(ProductContext::class);

    /** @var ProductContextInstance $ctx */
    return new SanctumAuthAdapter($ctx);
}

it('issues a token and returns a TokenResult', function (): void {
    $user = createTestUser();
    $adapter = makeAdapter();

    $result = $adapter->issueToken(user: $user, tokenName: 'test-token');

    expect($result)
        ->toBeInstanceOf(TokenResult::class)
        ->and($result->token)->toBeString()->not->toBeEmpty()
        ->and($result->tokenId)->toBeString()->not->toBeEmpty();
});

it('includes product ability when context has a product', function (): void {
    $user = createTestUser();
    $product = createTestProduct();

    $context = new ProductContextInstance();
    $context->setProduct($product);

    $adapter = makeAdapter($context);
    $result = $adapter->issueToken(user: $user, tokenName: 'scoped-token');

    $token = $user->tokens()->where('id', $result->tokenId)->first();

    expect($token)->not->toBeNull()
        ->and($token->abilities)->toContain("product:test-product");
});

it('does not include product ability when no context', function (): void {
    $user = createTestUser();
    $adapter = makeAdapter();

    $result = $adapter->issueToken(user: $user, tokenName: 'unscoped-token');

    $token = $user->tokens()->where('id', $result->tokenId)->first();

    expect($token)->not->toBeNull()
        ->and($token->abilities)->not->toContain('product:test-product');
});

it('revokes a specific token', function (): void {
    $user = createTestUser();
    $adapter = makeAdapter();

    $result = $adapter->issueToken(user: $user, tokenName: 'revoke-me');
    $adapter->revokeToken(user: $user, tokenId: $result->tokenId);

    expect($user->tokens()->where('id', $result->tokenId)->exists())->toBeFalse();
});

it('revokes all tokens', function (): void {
    $user = createTestUser();
    $adapter = makeAdapter();

    $adapter->issueToken(user: $user, tokenName: 'token-1');
    $adapter->issueToken(user: $user, tokenName: 'token-2');
    $adapter->revokeAllTokens($user);

    expect($user->tokens()->count())->toBe(0);
});

it('grants product access', function (): void {
    $user = createTestUser();
    $product = createTestProduct();
    $adapter = makeAdapter();

    $adapter->grantProductAccess(user: $user, product: $product, role: 'admin');

    $this->assertDatabaseHas('dayone_user_products', [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'role' => 'admin',
    ]);
});

it('revokes product access', function (): void {
    $user = createTestUser();
    $product = createTestProduct();
    $adapter = makeAdapter();

    $adapter->grantProductAccess(user: $user, product: $product);
    $adapter->revokeProductAccess(user: $user, product: $product);

    $this->assertDatabaseMissing('dayone_user_products', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});

it('returns true when user has product access', function (): void {
    $user = createTestUser();
    $product = createTestProduct();
    $adapter = makeAdapter();

    $adapter->grantProductAccess(user: $user, product: $product);

    expect($adapter->hasProductAccess(user: $user, product: $product))->toBeTrue();
});

it('returns false when user has no product access', function (): void {
    $user = createTestUser();
    $product = createTestProduct();
    $adapter = makeAdapter();

    expect($adapter->hasProductAccess(user: $user, product: $product))->toBeFalse();
});

it('dispatches ProductAccessGranted event on grant', function (): void {
    Event::fake([ProductAccessGranted::class]);

    $user = createTestUser();
    $product = createTestProduct();
    $adapter = makeAdapter();

    $adapter->grantProductAccess(user: $user, product: $product, role: 'editor');

    Event::assertDispatched(ProductAccessGranted::class, function (ProductAccessGranted $event) use ($user, $product): bool {
        return $event->user->getAuthIdentifier() === $user->getAuthIdentifier()
            && $event->product->is($product)
            && $event->role === 'editor';
    });
});

it('dispatches ProductAccessRevoked event on revoke', function (): void {
    Event::fake([ProductAccessRevoked::class]);

    $user = createTestUser();
    $product = createTestProduct();
    $adapter = makeAdapter();

    $adapter->grantProductAccess(user: $user, product: $product);
    $adapter->revokeProductAccess(user: $user, product: $product);

    Event::assertDispatched(ProductAccessRevoked::class, function (ProductAccessRevoked $event) use ($user, $product): bool {
        return $event->user->getAuthIdentifier() === $user->getAuthIdentifier()
            && $event->product->is($product);
    });
});

it('dispatches TokenIssued event on issue', function (): void {
    Event::fake([TokenIssued::class]);

    $user = createTestUser();
    $adapter = makeAdapter();

    $adapter->issueToken(user: $user, tokenName: 'event-test');

    Event::assertDispatched(TokenIssued::class, function (TokenIssued $event) use ($user): bool {
        return $event->user->getAuthIdentifier() === $user->getAuthIdentifier()
            && $event->tokenId !== ''
            && $event->productSlug === null;
    });
});

it('dispatches TokenRevoked event on revoke', function (): void {
    Event::fake([TokenRevoked::class]);

    $user = createTestUser();
    $adapter = makeAdapter();

    $result = $adapter->issueToken(user: $user, tokenName: 'revoke-event-test');
    $adapter->revokeToken(user: $user, tokenId: $result->tokenId);

    Event::assertDispatched(TokenRevoked::class, function (TokenRevoked $event) use ($user, $result): bool {
        return $event->user->getAuthIdentifier() === $user->getAuthIdentifier()
            && $event->tokenId === $result->tokenId;
    });
});
