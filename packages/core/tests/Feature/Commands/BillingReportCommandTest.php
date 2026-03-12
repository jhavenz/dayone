<?php

declare(strict_types=1);

use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Product;
use DayOne\Models\Subscription;

it('shows subscription status breakdown', function (): void {
    $product = Product::create(['name' => 'Report App', 'slug' => 'report-app', 'is_active' => true]);

    Subscription::create([
        'user_id' => 1,
        'product_id' => $product->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::create([
        'user_id' => 2,
        'product_id' => $product->id,
        'status' => SubscriptionStatus::Canceled,
    ]);

    $this->artisan('dayone:billing:report')
        ->assertSuccessful()
        ->expectsOutputToContain('Billing Report')
        ->expectsOutputToContain('active')
        ->expectsOutputToContain('canceled');
});

it('works with no subscriptions', function (): void {
    $this->artisan('dayone:billing:report')
        ->assertSuccessful()
        ->expectsOutputToContain('No subscriptions found.');
});
