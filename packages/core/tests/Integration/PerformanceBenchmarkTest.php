<?php

declare(strict_types=1);

use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Product;
use DayOne\Models\Subscription;
use DayOne\Runtime\DefaultProductResolver;
use DayOne\Runtime\ProductContextInstance;
use Illuminate\Http\Request;

it('resolves products within 10ms', function (): void {
    $products = [];
    for ($i = 0; $i < 100; $i++) {
        $products[] = Product::create([
            'name' => "Product {$i}",
            'slug' => "product-{$i}",
            'is_active' => true,
        ]);
    }

    $resolver = new DefaultProductResolver();
    $request = Request::create('/', 'GET', [], [], [], ['HTTP_X_PRODUCT' => 'product-50']);

    $start = hrtime(true);
    $resolved = $resolver->resolve($request);
    $elapsed = (hrtime(true) - $start) / 1_000_000;

    expect($resolved)->not->toBeNull()
        ->and($resolved->slug)->toBe('product-50')
        ->and($elapsed)->toBeLessThan(10.0);
});

it('creates product context within 5ms', function (): void {
    $product = Product::create(['name' => 'Fast', 'slug' => 'fast-product', 'is_active' => true]);

    $start = hrtime(true);
    $context = new ProductContextInstance();
    $context->setProduct($product);
    $elapsed = (hrtime(true) - $start) / 1_000_000;

    expect($context->hasProduct())->toBeTrue()
        ->and($elapsed)->toBeLessThan(5.0);
});

it('adds minimal overhead with BelongsToProduct scope', function (): void {
    $product = Product::create(['name' => 'Bench', 'slug' => 'bench', 'is_active' => true]);

    for ($i = 0; $i < 50; $i++) {
        Subscription::withoutGlobalScopes()->create([
            'user_id' => 1,
            'product_id' => $product->id,
            'status' => SubscriptionStatus::Active,
        ]);
    }

    $unscopedStart = hrtime(true);
    Subscription::withoutGlobalScopes()->get();
    $unscopedTime = (hrtime(true) - $unscopedStart) / 1_000_000;

    /** @var ProductContextInstance $context */
    $context = app(ProductContextInstance::class);
    $context->setProduct($product);

    $scopedStart = hrtime(true);
    Subscription::all();
    $scopedTime = (hrtime(true) - $scopedStart) / 1_000_000;

    /**
     * Scoped query should be within 2x of unscoped.
     * The WHERE clause adds minimal overhead on small datasets.
     */
    expect($scopedTime)->toBeLessThan($unscopedTime * 2 + 1.0);
});
