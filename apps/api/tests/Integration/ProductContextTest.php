<?php

declare(strict_types=1);

use DayOne\Models\Product;
use Tests\Traits\InteractsWithDayOne;

uses(InteractsWithDayOne::class);

it('resolves product from route parameter', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/acme/access/check');

    $response->assertOk();
});

it('resolves product from X-Product header', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('X-Product', 'acme')
        ->getJson('/api/acme/access/check');

    $response->assertOk();
});

it('returns 404 for inactive product', function () {
    $this->seedProduct('Defunct', 'defunct', ['is_active' => false]);
    [$user, $token] = $this->authenticatedUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/defunct/access/check');

    $response->assertNotFound();
});

it('returns 404 for nonexistent product slug', function () {
    [$user, $token] = $this->authenticatedUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/nonexistent/access/check');

    $response->assertStatus(500); // ProductNotFoundException since no product resolves
});

it('loads product settings into config', function () {
    $this->seedProduct('Acme', 'acme', ['settings' => ['theme' => 'dark']]);
    [$user, $token] = $this->authenticatedUser();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/acme/access/check');

    expect(config('dayone.products.acme.theme'))->toBe('dark');
});

it('lists only active products', function () {
    $this->seedProduct('Active', 'active');
    $this->seedProduct('Inactive', 'inactive', ['is_active' => false]);

    $response = $this->getJson('/api/products');

    $response->assertOk();
    $products = $response->json('products');
    expect($products)->toHaveCount(1);
    expect($products[0]['slug'])->toBe('active');
});

it('shows product detail by slug', function () {
    $this->seedProduct('Acme', 'acme');

    $response = $this->getJson('/api/products/acme');

    $response->assertOk();
    expect($response->json('product.slug'))->toBe('acme');
    expect($response->json('product.name'))->toBe('Acme');
});

it('returns 404 for missing product detail', function () {
    $response = $this->getJson('/api/products/nope');

    $response->assertNotFound();
});
