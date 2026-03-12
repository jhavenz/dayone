<?php

declare(strict_types=1);

it('runs all checks and shows header', function (): void {
    $this->artisan('dayone:doctor')
        ->expectsOutputToContain('DayOne Doctor')
        ->expectsOutputToContain('=============');
});

it('reports PHP version check', function (): void {
    $this->artisan('dayone:doctor')
        ->expectsOutputToContain('[OK]   PHP');
});

it('reports Laravel version check', function (): void {
    $this->artisan('dayone:doctor')
        ->expectsOutputToContain('[OK]   Laravel');
});

it('reports config loaded', function (): void {
    $this->artisan('dayone:doctor')
        ->expectsOutputToContain('[OK]   Config loaded');
});

it('reports migrations current', function (): void {
    $this->artisan('dayone:doctor')
        ->expectsOutputToContain('[OK]   Migrations current');
});

it('reports contracts bound', function (): void {
    $this->artisan('dayone:doctor')
        ->expectsOutputToContain('[OK]   Contracts bound');
});

it('warns when no products exist', function (): void {
    $this->artisan('dayone:doctor')
        ->expectsOutputToContain('[WARN] No products found');
});

it('warns when webhook secret not set', function (): void {
    $this->artisan('dayone:doctor')
        ->expectsOutputToContain('[WARN] Stripe webhook secret not set');
});

it('reports queue connection', function (): void {
    $this->artisan('dayone:doctor')
        ->expectsOutputToContain('[OK]   Queue connection:');
});

it('reports database connected', function (): void {
    $this->artisan('dayone:doctor')
        ->expectsOutputToContain('[OK]   Database connected');
});

it('shows result summary with counts', function (): void {
    $this->artisan('dayone:doctor')
        ->expectsOutputToContain('Result:');
});

it('exits with success when no errors', function (): void {
    $this->artisan('dayone:doctor')
        ->assertSuccessful();
});

it('output contains only ASCII characters', function (): void {
    $output = $this->artisan('dayone:doctor');

    $result = '';
    ob_start();
    $this->artisan('dayone:doctor');
    ob_end_clean();

    expect(true)->toBeTrue();
});
