<?php

declare(strict_types=1);

use DayOne\Ejection\EjectionManager;
use DayOne\Events\ConcernAdopted;
use DayOne\Events\ConcernEjected;
use DayOne\Models\Ejection;
use DayOne\Models\Product;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'is_active' => true,
    ]);

    $this->manager = app(EjectionManager::class);
});

it('can eject a product from a concern', function (): void {
    Event::fake();

    $this->manager->eject($this->product, 'billing');

    $ejection = Ejection::withoutGlobalScopes()
        ->where('product_id', $this->product->id)
        ->where('concern', 'billing')
        ->first();

    expect($ejection)->not->toBeNull()
        ->and($ejection->concern)->toBe('billing');
});

it('can adopt a product back', function (): void {
    Event::fake();

    $this->manager->eject($this->product, 'billing');
    $this->manager->adopt($this->product, 'billing');

    $ejection = Ejection::withoutGlobalScopes()
        ->where('product_id', $this->product->id)
        ->where('concern', 'billing')
        ->first();

    expect($ejection)->toBeNull();
});

it('isEjected returns true after eject', function (): void {
    Event::fake();

    $this->manager->eject($this->product, 'auth');

    expect($this->manager->isEjected($this->product, 'auth'))->toBeTrue();
});

it('isEjected returns false after adopt', function (): void {
    Event::fake();

    $this->manager->eject($this->product, 'auth');
    $this->manager->adopt($this->product, 'auth');

    expect($this->manager->isEjected($this->product, 'auth'))->toBeFalse();
});

it('getEjections returns ejected concerns', function (): void {
    Event::fake();

    $this->manager->eject($this->product, 'billing');
    $this->manager->eject($this->product, 'admin');

    $ejections = $this->manager->getEjections($this->product);

    expect($ejections)->toContain('billing')
        ->toContain('admin')
        ->toHaveCount(2);
});

it('invalid concern throws exception', function (): void {
    $this->manager->eject($this->product, 'invalid');
})->throws(\DayOne\Exceptions\InvalidConcernException::class);

it('double eject is idempotent', function (): void {
    Event::fake();

    $this->manager->eject($this->product, 'billing');
    $this->manager->eject($this->product, 'billing');

    $count = Ejection::withoutGlobalScopes()
        ->where('product_id', $this->product->id)
        ->where('concern', 'billing')
        ->count();

    expect($count)->toBe(1);
});

it('dispatches ConcernEjected event on eject', function (): void {
    Event::fake();

    $this->manager->eject($this->product, 'billing', 'switching to custom');

    Event::assertDispatched(ConcernEjected::class, function (ConcernEjected $event): bool {
        return $event->productSlug === 'test-product'
            && $event->concern === 'billing'
            && $event->reason === 'switching to custom';
    });
});

it('dispatches ConcernAdopted event on adopt', function (): void {
    Event::fake();

    $this->manager->eject($this->product, 'billing');
    $this->manager->adopt($this->product, 'billing');

    Event::assertDispatched(ConcernAdopted::class, function (ConcernAdopted $event): bool {
        return $event->productSlug === 'test-product'
            && $event->concern === 'billing';
    });
});
