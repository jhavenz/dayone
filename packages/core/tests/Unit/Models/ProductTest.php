<?php

declare(strict_types=1);

use DayOne\Models\Product;

it('can be created with required attributes', function (): void {
    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
    ]);

    expect($product)
        ->toBeInstanceOf(Product::class)
        ->name->toBe('Test Product')
        ->slug->toBe('test-product');
});

it('has the correct table name', function (): void {
    $product = new Product();

    expect($product->getTable())->toBe('dayone_products');
});

it('exposes isActive via property hook', function (): void {
    $active = Product::create([
        'name' => 'Active',
        'slug' => 'active',
        'is_active' => true,
    ]);

    $inactive = Product::create([
        'name' => 'Inactive',
        'slug' => 'inactive',
        'is_active' => false,
    ]);

    expect($active->isActive)->toBeTrue()
        ->and($inactive->isActive)->toBeFalse();
});

it('exposes settingsArray via property hook', function (): void {
    $product = Product::create([
        'name' => 'With Settings',
        'slug' => 'with-settings',
        'settings' => ['stripe_key' => 'sk_test_123'],
    ]);

    expect($product->settingsArray)
        ->toBeArray()
        ->toHaveKey('stripe_key', 'sk_test_123');
});

it('returns empty array when settings is null', function (): void {
    $product = Product::create([
        'name' => 'No Settings',
        'slug' => 'no-settings',
    ]);

    expect($product->settingsArray)->toBe([]);
});
