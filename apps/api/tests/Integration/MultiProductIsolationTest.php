<?php

declare(strict_types=1);

use App\Models\User;
use DayOne\Contracts\Auth\V1\AuthManager;
use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Subscription;
use DayOne\Runtime\ProductContextInstance;
use Illuminate\Support\Facades\DB;
use Tests\Traits\InteractsWithDayOne;

uses(InteractsWithDayOne::class);

it('isolates product access between products via HTTP', function () {
    $acme = $this->seedProduct('Acme', 'acme');
    $beta = $this->seedProduct('Beta', 'beta');
    [$user, $token] = $this->authenticatedUser();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant');

    $acmeCheck = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/acme/access/check');
    $betaCheck = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/beta/access/check');

    expect($acmeCheck->json('has_access'))->toBeTrue();
    expect($betaCheck->json('has_access'))->toBeFalse();
});

it('allows user to have access to multiple products', function () {
    $acme = $this->seedProduct('Acme', 'acme');
    $beta = $this->seedProduct('Beta', 'beta');
    [$user, $token] = $this->authenticatedUser();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant');
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/beta/access/grant');

    $acmeCheck = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/acme/access/check');
    $betaCheck = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/beta/access/check');

    expect($acmeCheck->json('has_access'))->toBeTrue();
    expect($betaCheck->json('has_access'))->toBeTrue();
});

it('isolates subscriptions between products', function () {
    $acme = $this->seedProduct('Acme', 'acme');
    $beta = $this->seedProduct('Beta', 'beta');
    [$user, $token] = $this->authenticatedUser();

    $acmeSub = $this->seedSubscription($user, $acme, SubscriptionStatus::Active);
    $betaSub = $this->seedSubscription($user, $beta, SubscriptionStatus::Trialing);

    // Check acme subscription
    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/acme/subscription?subscription_id=' . $acmeSub->id);
    expect($response->json('status'))->toBe('active');

    // Check beta subscription
    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/beta/subscription?subscription_id=' . $betaSub->id);
    expect($response->json('status'))->toBe('trialing');
});

it('prevents cross-product subscription leakage in database', function () {
    $acme = $this->seedProduct('Acme', 'acme');
    $beta = $this->seedProduct('Beta', 'beta');

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $this->seedSubscription($user1, $acme, SubscriptionStatus::Active);
    $this->seedSubscription($user2, $beta, SubscriptionStatus::Active);

    $context = app(ProductContextInstance::class);
    $context->setProduct($acme);

    $acmeSubs = Subscription::all();
    expect($acmeSubs)->toHaveCount(1);
    expect($acmeSubs->first()->user_id)->toBe($user1->id);
});

it('scopes product listing to active only', function () {
    $this->seedProduct('Active1', 'active1');
    $this->seedProduct('Active2', 'active2');
    $this->seedProduct('Inactive', 'inactive', ['is_active' => false]);

    $response = $this->getJson('/api/products');
    $products = $response->json('products');

    expect($products)->toHaveCount(2);
    $slugs = array_column($products, 'slug');
    expect($slugs)->toContain('active1', 'active2');
    expect($slugs)->not->toContain('inactive');
});

it('maintains separate user-product roles per product', function () {
    $acme = $this->seedProduct('Acme', 'acme');
    $beta = $this->seedProduct('Beta', 'beta');
    [$user, $token] = $this->authenticatedUser();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant', ['role' => 'admin']);
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/beta/access/grant', ['role' => 'user']);

    $acmeRole = DB::table('dayone_user_products')
        ->where('user_id', $user->id)
        ->where('product_id', $acme->id)
        ->value('role');
    $betaRole = DB::table('dayone_user_products')
        ->where('user_id', $user->id)
        ->where('product_id', $beta->id)
        ->value('role');

    expect($acmeRole)->toBe('admin');
    expect($betaRole)->toBe('user');
});

it('does not leak data between product routes', function () {
    $acme = $this->seedProduct('Acme', 'acme');
    $beta = $this->seedProduct('Beta', 'beta');
    [$user, $token] = $this->authenticatedUser();

    $auth = app(AuthManager::class);
    $auth->grantProductAccess($user, $acme, 'admin');

    // Access check on acme (should have access)
    $acmeCheck = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/acme/access/check');
    expect($acmeCheck->json('has_access'))->toBeTrue();

    // Access check on beta (should NOT have access)
    $betaCheck = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/beta/access/check');
    expect($betaCheck->json('has_access'))->toBeFalse();
});

it('handles multiple users across same product independently', function () {
    $acme = $this->seedProduct('Acme', 'acme');
    [$user1, $token1] = $this->authenticatedUser();
    [$user2, $token2] = $this->authenticatedUser();

    $this->withHeader('Authorization', "Bearer {$token1}")
        ->postJson('/api/acme/access/grant');

    $auth = app(\DayOne\Contracts\Auth\V1\AuthManager::class);
    expect($auth->hasProductAccess($user1, $acme))->toBeTrue();
    expect($auth->hasProductAccess($user2, $acme))->toBeFalse();
});
