<?php

declare(strict_types=1);

use DayOne\Events\ProductArchived;
use DayOne\Events\ProductHibernated;
use DayOne\Events\ProductWoken;
use DayOne\Models\Product;
use Illuminate\Support\Facades\Event;

it('hibernate sets is_active to false', function (): void {
    Event::fake();
    Product::create(['name' => 'Active App', 'slug' => 'active-app', 'is_active' => true]);

    $this->artisan('dayone:product:hibernate', ['slug' => 'active-app'])
        ->assertSuccessful();

    expect(Product::where('slug', 'active-app')->first()->isActive)->toBeFalse();
});

it('wake sets is_active to true', function (): void {
    Event::fake();
    Product::create(['name' => 'Sleeping App', 'slug' => 'sleeping-app', 'is_active' => false]);

    $this->artisan('dayone:product:wake', ['slug' => 'sleeping-app'])
        ->assertSuccessful();

    expect(Product::where('slug', 'sleeping-app')->first()->isActive)->toBeTrue();
});

it('archive sets is_active to false', function (): void {
    Event::fake();
    Product::create(['name' => 'Old App', 'slug' => 'old-app', 'is_active' => true]);

    $this->artisan('dayone:product:archive', ['slug' => 'old-app'])
        ->assertSuccessful();

    expect(Product::where('slug', 'old-app')->first()->isActive)->toBeFalse();
});

it('hibernate dispatches ProductHibernated event', function (): void {
    Event::fake();
    Product::create(['name' => 'App', 'slug' => 'event-app', 'is_active' => true]);

    $this->artisan('dayone:product:hibernate', ['slug' => 'event-app']);

    Event::assertDispatched(ProductHibernated::class, function (ProductHibernated $event): bool {
        return $event->productSlug === 'event-app';
    });
});

it('wake dispatches ProductWoken event', function (): void {
    Event::fake();
    Product::create(['name' => 'App', 'slug' => 'wake-app', 'is_active' => false]);

    $this->artisan('dayone:product:wake', ['slug' => 'wake-app']);

    Event::assertDispatched(ProductWoken::class, function (ProductWoken $event): bool {
        return $event->productSlug === 'wake-app';
    });
});

it('archive dispatches ProductArchived event', function (): void {
    Event::fake();
    Product::create(['name' => 'App', 'slug' => 'archive-app', 'is_active' => true]);

    $this->artisan('dayone:product:archive', ['slug' => 'archive-app']);

    Event::assertDispatched(ProductArchived::class, function (ProductArchived $event): bool {
        return $event->productSlug === 'archive-app';
    });
});

it('hibernate fails gracefully for non-existent slug', function (): void {
    $this->artisan('dayone:product:hibernate', ['slug' => 'nonexistent'])
        ->assertFailed();
});

it('wake fails gracefully for non-existent slug', function (): void {
    $this->artisan('dayone:product:wake', ['slug' => 'nonexistent'])
        ->assertFailed();
});

it('archive fails gracefully for non-existent slug', function (): void {
    $this->artisan('dayone:product:archive', ['slug' => 'nonexistent'])
        ->assertFailed();
});
