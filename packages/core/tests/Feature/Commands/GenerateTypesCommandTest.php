<?php

declare(strict_types=1);

use DayOne\Models\Product;

it('requires the --product option', function (): void {
    $this->artisan('dayone:types:generate')
        ->expectsOutputToContain('--product option is required')
        ->assertExitCode(1);
});

it('fails when product does not exist', function (): void {
    $this->artisan('dayone:types:generate', ['--product' => 'nonexistent'])
        ->expectsOutputToContain('Product [nonexistent] not found')
        ->assertExitCode(1);
});

it('outputs the OpenAPI spec URL for a valid product', function (): void {
    Product::create([
        'name' => 'Test App',
        'slug' => 'test-app',
        'is_active' => true,
    ]);

    $this->artisan('dayone:types:generate', ['--product' => 'test-app'])
        ->expectsOutputToContain('OpenAPI spec URL:')
        ->expectsOutputToContain('test-app')
        ->assertExitCode(0);
});
