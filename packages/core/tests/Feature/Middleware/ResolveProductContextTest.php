<?php

declare(strict_types=1);

use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\Models\Product;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware('dayone.product')->get('/test-product', function (ProductContext $context) {
        return response()->json([
            'has_product' => $context->hasProduct(),
            'product_slug' => $context->product()?->slug,
        ]);
    });
});

it('resolves product from X-Product header', function (): void {
    Product::create([
        'name' => 'Middleware Product',
        'slug' => 'mw-product',
    ]);

    $response = $this->get('/test-product', ['X-Product' => 'mw-product']);

    $response->assertOk()
        ->assertJson([
            'has_product' => true,
            'product_slug' => 'mw-product',
        ]);
});

it('passes through when no product is found', function (): void {
    $response = $this->get('/test-product');

    $response->assertOk()
        ->assertJson([
            'has_product' => false,
            'product_slug' => null,
        ]);
});

it('makes context available after middleware resolves', function (): void {
    Product::create([
        'name' => 'Context Product',
        'slug' => 'ctx-product',
        'settings' => ['theme' => 'dark'],
    ]);

    $response = $this->get('/test-product', ['X-Product' => 'ctx-product']);

    $response->assertOk()
        ->assertJson([
            'has_product' => true,
            'product_slug' => 'ctx-product',
        ]);

    expect(config('dayone.products.ctx-product.theme'))->toBe('dark');
});
