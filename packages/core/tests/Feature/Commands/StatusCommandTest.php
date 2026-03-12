<?php

declare(strict_types=1);

it('shows concise status line', function (): void {
    $this->artisan('dayone:status')
        ->expectsOutputToContain('DayOne Status:');
});

it('reports OK when system is healthy', function (): void {
    $this->artisan('dayone:status')
        ->expectsOutputToContain('DayOne Status: OK')
        ->assertSuccessful();
});

it('includes check count in output', function (): void {
    $this->artisan('dayone:status')
        ->expectsOutputToContain('checks passed');
});
