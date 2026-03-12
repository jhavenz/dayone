<?php

declare(strict_types=1);

use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Product;
use DayOne\Models\Subscription;

it('lists products in table format', function (): void {
    Product::create(['name' => 'App One', 'slug' => 'app-one', 'is_active' => true]);
    Product::create(['name' => 'App Two', 'slug' => 'app-two', 'is_active' => false]);

    $this->artisan('dayone:product:list')
        ->assertSuccessful()
        ->expectsTable(
            ['Slug', 'Name', 'Status', 'Subscribers', 'MRR'],
            [
                ['app-one', 'App One', 'active', '0', '0'],
                ['app-two', 'App Two', 'inactive', '0', '0'],
            ],
        );
});

it('shows subscriber count', function (): void {
    $product = Product::create(['name' => 'Sub App', 'slug' => 'sub-app', 'is_active' => true]);

    Subscription::create([
        'user_id' => 1,
        'product_id' => $product->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $this->artisan('dayone:product:list')
        ->assertSuccessful()
        ->expectsTable(
            ['Slug', 'Name', 'Status', 'Subscribers', 'MRR'],
            [
                ['sub-app', 'Sub App', 'active', '1', '1'],
            ],
        );
});

it('works with no products', function (): void {
    $this->artisan('dayone:product:list')
        ->assertSuccessful()
        ->expectsOutput('No products found.');
});
