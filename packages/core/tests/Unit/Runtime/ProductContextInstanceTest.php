<?php

declare(strict_types=1);

use DayOne\Models\Product;
use DayOne\Runtime\ProductContextInstance;

it('starts with no product', function (): void {
    $context = new ProductContextInstance();

    expect($context->product())->toBeNull()
        ->and($context->hasProduct())->toBeFalse();
});

it('can set and get a product', function (): void {
    $product = Product::create([
        'name' => 'Test',
        'slug' => 'test',
    ]);

    $context = new ProductContextInstance();
    $context->setProduct($product);

    expect($context->product())->toBe($product)
        ->and($context->hasProduct())->toBeTrue();
});

it('throws when requireProduct is called without a product', function (): void {
    $context = new ProductContextInstance();

    $context->requireProduct();
})->throws(\DayOne\Exceptions\ProductNotFoundException::class);

it('returns product from requireProduct when set', function (): void {
    $product = Product::create([
        'name' => 'Test',
        'slug' => 'require-test',
    ]);

    $context = new ProductContextInstance();
    $context->setProduct($product);

    expect($context->requireProduct())->toBe($product);
});
