<?php

declare(strict_types=1);

use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\Models\Product;
use DayOne\Runtime\ProductContextInstance;
use DayOne\Tests\Fixtures\Jobs\TestProductAwareJob;

it('serializes product context via ProductAware trait', function (): void {
    $product = Product::create(['name' => 'Queue Test', 'slug' => 'queue-test', 'is_active' => true]);

    /** @var ProductContextInstance $context */
    $context = app(ProductContextInstance::class);
    $context->setProduct($product);

    $job = new TestProductAwareJob();

    expect($job->productId)->toBe($product->id);

    $serialized = serialize($job);
    /** @var TestProductAwareJob $deserialized */
    $deserialized = unserialize($serialized);

    expect($deserialized->productId)->toBe($product->id);
});

it('restores product context after deserialization round-trip', function (): void {
    $product = Product::create(['name' => 'Round Trip', 'slug' => 'round-trip', 'is_active' => true]);

    /** @var ProductContextInstance $context */
    $context = app(ProductContextInstance::class);
    $context->setProduct($product);

    $job = new TestProductAwareJob();

    $serialized = serialize($job);
    /** @var TestProductAwareJob $deserialized */
    $deserialized = unserialize($serialized);

    $deserialized->restoreProductContext();

    /** @var ProductContextInstance $restoredContext */
    $restoredContext = app(ProductContextInstance::class);

    expect($restoredContext->hasProduct())->toBeTrue()
        ->and($restoredContext->product()?->slug)->toBe('round-trip');
});
