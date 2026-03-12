<?php

declare(strict_types=1);

use DayOne\Ejection\EjectionManager;
use DayOne\Events\ConcernAdopted;
use DayOne\Models\Ejection;
use DayOne\Models\Product;
use Illuminate\Support\Facades\Event;

it('adopts a product back to billing', function (): void {
    Event::fake();
    $product = Product::create(['name' => 'Adopt App', 'slug' => 'adopt-app', 'is_active' => true]);

    app(EjectionManager::class)->eject($product, 'billing');

    $this->artisan('dayone:adopt', ['slug' => 'adopt-app', 'concern' => 'billing'])
        ->assertSuccessful();

    $ejection = Ejection::withoutGlobalScopes()
        ->where('product_id', $product->id)
        ->where('concern', 'billing')
        ->first();

    expect($ejection)->toBeNull();
});

it('handles non-ejected concern gracefully', function (): void {
    Event::fake();
    Product::create(['name' => 'Clean App', 'slug' => 'clean-app', 'is_active' => true]);

    $this->artisan('dayone:adopt', ['slug' => 'clean-app', 'concern' => 'billing'])
        ->assertSuccessful();
});
