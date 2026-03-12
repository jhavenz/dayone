<?php

declare(strict_types=1);

use DayOne\Events\ProductArchived;
use DayOne\Events\ProductCreated;
use DayOne\Events\ProductHibernated;
use DayOne\Events\ProductWoken;

it('ProductCreated carries slug and name', function (): void {
    $event = new ProductCreated(productSlug: 'my-app', productName: 'My App');

    expect($event->productSlug)->toBe('my-app')
        ->and($event->productName)->toBe('My App');
});

it('ProductHibernated carries slug', function (): void {
    $event = new ProductHibernated(productSlug: 'sleeping-app');

    expect($event->productSlug)->toBe('sleeping-app');
});

it('ProductWoken carries slug', function (): void {
    $event = new ProductWoken(productSlug: 'waking-app');

    expect($event->productSlug)->toBe('waking-app');
});

it('ProductArchived carries slug', function (): void {
    $event = new ProductArchived(productSlug: 'archived-app');

    expect($event->productSlug)->toBe('archived-app');
});
