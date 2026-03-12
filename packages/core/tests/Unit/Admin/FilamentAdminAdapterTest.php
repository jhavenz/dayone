<?php

declare(strict_types=1);

use DayOne\Adapters\Admin\FilamentAdminAdapter;
use DayOne\Models\Product;

it('can register a product panel', function (): void {
    $adapter = new FilamentAdminAdapter();
    $product = Product::create([
        'name' => 'Test App',
        'slug' => 'test-app',
    ]);

    $adapter->registerPanel($product);

    expect($adapter->getRegisteredPanels())->toHaveKey('test-app');
});

it('returns registered panels', function (): void {
    $adapter = new FilamentAdminAdapter();
    $productA = Product::create(['name' => 'App A', 'slug' => 'app-a']);
    $productB = Product::create(['name' => 'App B', 'slug' => 'app-b']);

    $adapter->registerPanel($productA);
    $adapter->registerPanel($productB);

    expect($adapter->getRegisteredPanels())
        ->toHaveCount(2)
        ->toHaveKeys(['app-a', 'app-b']);
});

it('does not duplicate when registering the same product twice', function (): void {
    $adapter = new FilamentAdminAdapter();
    $product = Product::create(['name' => 'Dup', 'slug' => 'dup']);

    $adapter->registerPanel($product);
    $adapter->registerPanel($product);

    expect($adapter->getRegisteredPanels())->toHaveCount(1);
});
