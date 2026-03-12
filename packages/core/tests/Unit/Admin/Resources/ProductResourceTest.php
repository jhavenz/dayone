<?php

declare(strict_types=1);

use DayOne\Admin\Resources\ProductResource;
use DayOne\Admin\Resources\ProductResource\Pages\CreateProduct;
use DayOne\Admin\Resources\ProductResource\Pages\EditProduct;
use DayOne\Admin\Resources\ProductResource\Pages\ListProducts;
use DayOne\Models\Product;

it('uses the correct model', function (): void {
    expect(ProductResource::getModel())->toBe(Product::class);
});

it('has form method that returns a schema', function (): void {
    $method = new ReflectionMethod(ProductResource::class, 'form');

    expect($method->isStatic())->toBeTrue()
        ->and($method->isPublic())->toBeTrue();
});

it('has table method that returns a table', function (): void {
    $method = new ReflectionMethod(ProductResource::class, 'table');

    expect($method->isStatic())->toBeTrue()
        ->and($method->isPublic())->toBeTrue();
});

it('has the correct pages', function (): void {
    $pages = ProductResource::getPages();

    expect($pages)
        ->toHaveKeys(['index', 'create', 'edit'])
        ->and($pages['index']->getPage())->toBe(ListProducts::class)
        ->and($pages['create']->getPage())->toBe(CreateProduct::class)
        ->and($pages['edit']->getPage())->toBe(EditProduct::class);
});
