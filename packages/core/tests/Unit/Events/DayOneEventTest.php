<?php

declare(strict_types=1);

use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\Events\DayOneEvent;
use DayOne\Models\Product;

it('captures product slug from string constructor argument', function (): void {
    $event = new class ('my-product') extends DayOneEvent {};

    expect($event->productSlug)->toBe('my-product');
});

it('captures product slug from Product model', function (): void {
    $product = Product::create(['name' => 'Test', 'slug' => 'test-slug', 'is_active' => true]);

    $event = new class ($product) extends DayOneEvent {};

    expect($event->productSlug)->toBe('test-slug');
});

it('resolves product slug from context when not provided', function (): void {
    $product = Product::create(['name' => 'Context Product', 'slug' => 'ctx-product', 'is_active' => true]);

    $context = Mockery::mock(ProductContext::class);
    $context->shouldReceive('hasProduct')->andReturn(true);
    $context->shouldReceive('product')->andReturn($product);

    app()->instance(ProductContext::class, $context);

    $event = new class () extends DayOneEvent {};

    expect($event->productSlug)->toBe('ctx-product');
});

it('sets product slug to null when no product and no context', function (): void {
    $context = Mockery::mock(ProductContext::class);
    $context->shouldReceive('hasProduct')->andReturn(false);

    app()->instance(ProductContext::class, $context);

    $event = new class () extends DayOneEvent {};

    expect($event->productSlug)->toBeNull();
});
