<?php

declare(strict_types=1);

it('publishes config', function (): void {
    $this->artisan('dayone:install')
        ->expectsOutputToContain('Installing DayOne');
});

it('runs without error', function (): void {
    $this->artisan('dayone:install')
        ->assertSuccessful();
});
