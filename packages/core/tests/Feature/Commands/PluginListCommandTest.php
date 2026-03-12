<?php

declare(strict_types=1);

use DayOne\Models\Plugin;

it('lists installed plugins', function (): void {
    Plugin::create([
        'name' => 'example-plugin',
        'version' => '2.0.0',
        'is_active' => true,
        'installed_at' => now(),
    ]);

    $this->artisan('dayone:plugin:list')
        ->assertSuccessful()
        ->expectsTable(
            ['Name', 'Version', 'Status', 'Products'],
            [
                ['example-plugin', '2.0.0', 'active', '(none)'],
            ],
        );
});

it('works with no plugins', function (): void {
    $this->artisan('dayone:plugin:list')
        ->assertSuccessful()
        ->expectsOutput('No plugins installed.');
});
