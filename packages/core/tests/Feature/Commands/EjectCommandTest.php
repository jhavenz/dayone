<?php

declare(strict_types=1);

use DayOne\Events\ConcernEjected;
use DayOne\Models\Ejection;
use DayOne\Models\Product;
use Illuminate\Support\Facades\Event;

it('ejects a product from billing', function (): void {
    Event::fake();
    Product::create(['name' => 'Eject App', 'slug' => 'eject-app', 'is_active' => true]);

    $this->artisan('dayone:eject', ['slug' => 'eject-app', 'concern' => 'billing'])
        ->assertSuccessful();

    $ejection = Ejection::withoutGlobalScopes()
        ->whereHas('product', fn ($q) => $q->withoutGlobalScopes()->where('slug', 'eject-app'))
        ->where('concern', 'billing')
        ->first();

    expect($ejection)->not->toBeNull();
});

it('rejects invalid concern', function (): void {
    Product::create(['name' => 'Bad App', 'slug' => 'bad-app', 'is_active' => true]);

    $this->artisan('dayone:eject', ['slug' => 'bad-app', 'concern' => 'invalid'])
        ->assertFailed();
});

it('handles non-existent product', function (): void {
    $this->artisan('dayone:eject', ['slug' => 'nonexistent', 'concern' => 'billing'])
        ->assertFailed();
});
