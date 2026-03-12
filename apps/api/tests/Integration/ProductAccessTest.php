<?php

declare(strict_types=1);

use DayOne\Events\ProductAccessGranted;
use DayOne\Events\ProductAccessRevoked;
use Illuminate\Support\Facades\Event;
use Tests\Traits\InteractsWithDayOne;

uses(InteractsWithDayOne::class);

it('grants product access to user', function () {
    Event::fake([ProductAccessGranted::class]);
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant');

    $response->assertOk();
    Event::assertDispatched(ProductAccessGranted::class);

    $this->assertDatabaseHas('dayone_user_products', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});

it('revokes product access from user', function () {
    Event::fake([ProductAccessRevoked::class]);
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    // Grant first
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant');

    // Then revoke
    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/revoke');

    $response->assertOk();
    Event::assertDispatched(ProductAccessRevoked::class);

    $this->assertDatabaseMissing('dayone_user_products', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});

it('checks product access returns true when granted', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant');

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/acme/access/check');

    $response->assertOk();
    expect($response->json('has_access'))->toBeTrue();
});

it('checks product access returns false when not granted', function () {
    $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/acme/access/check');

    $response->assertOk();
    expect($response->json('has_access'))->toBeFalse();
});

it('grants access with custom role', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant', ['role' => 'admin']);

    $this->assertDatabaseHas('dayone_user_products', [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'role' => 'admin',
    ]);
});

it('is idempotent on double grant', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant');
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant');

    $count = \Illuminate\Support\Facades\DB::table('dayone_user_products')
        ->where('user_id', $user->id)
        ->where('product_id', $product->id)
        ->count();

    expect($count)->toBe(1);
});

it('isolates access between products', function () {
    $acme = $this->seedProduct('Acme', 'acme');
    $beta = $this->seedProduct('Beta', 'beta');
    [$user, $token] = $this->authenticatedUser();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant');

    $checkAcme = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/acme/access/check');
    $checkBeta = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/beta/access/check');

    expect($checkAcme->json('has_access'))->toBeTrue();
    expect($checkBeta->json('has_access'))->toBeFalse();
});

it('can revoke without prior grant', function () {
    $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/revoke');

    $response->assertOk();
});
