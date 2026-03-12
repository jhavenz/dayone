<?php

declare(strict_types=1);

use App\Models\User;
use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Product;
use Illuminate\Support\Facades\DB;
use Tests\Traits\InteractsWithDayOne;

uses(InteractsWithDayOne::class);

it('handles product with null domain', function () {
    $product = $this->seedProduct('NoDomain', 'nodomain', ['domain' => null]);

    $response = $this->getJson('/api/products/nodomain');

    $response->assertOk();
    expect($response->json('product.domain'))->toBeNull();
});

it('handles product with empty settings', function () {
    $product = $this->seedProduct('Empty', 'empty', ['settings' => []]);

    $response = $this->getJson('/api/products/empty');

    $response->assertOk();
    expect($response->json('product.settings'))->toBe([]);
});

it('handles product with null settings', function () {
    $product = $this->seedProduct('NullSettings', 'nullsettings', ['settings' => null]);

    $response = $this->getJson('/api/products/nullsettings');

    $response->assertOk();
});

it('rejects registration with invalid email format', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Bad Email',
        'email' => 'not-an-email',
        'password' => 'password123',
    ]);

    $response->assertUnprocessable();
});

it('rejects registration with too short password', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Short Pass',
        'email' => 'short@test.com',
        'password' => 'abc',
    ]);

    $response->assertUnprocessable();
});

it('rejects registration with missing name', function () {
    $response = $this->postJson('/api/auth/register', [
        'email' => 'noname@test.com',
        'password' => 'password123',
    ]);

    $response->assertUnprocessable();
});

it('double-grant updates role instead of duplicating', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant', ['role' => 'user']);
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant', ['role' => 'admin']);

    $count = DB::table('dayone_user_products')
        ->where('user_id', $user->id)
        ->where('product_id', $product->id)
        ->count();
    $role = DB::table('dayone_user_products')
        ->where('user_id', $user->id)
        ->where('product_id', $product->id)
        ->value('role');

    expect($count)->toBe(1);
    expect($role)->toBe('admin');
});

it('handles concurrent product access grants', function () {
    $product = $this->seedProduct('Acme', 'acme');
    $users = User::factory()->count(3)->create();

    foreach ($users as $user) {
        $auth = app(\DayOne\Contracts\Auth\V1\AuthManager::class);
        $auth->grantProductAccess($user, $product);
    }

    $count = DB::table('dayone_user_products')
        ->where('product_id', $product->id)
        ->count();

    expect($count)->toBe(3);
});
