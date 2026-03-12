<?php

declare(strict_types=1);

use DayOne\Models\Plugin;
use DayOne\Plugins\PluginManager;
use DayOne\Tests\Fixtures\TestPlugin;

it('removes an installed plugin', function (): void {
    $manager = app(PluginManager::class);
    $manager->install(TestPlugin::class);

    $this->artisan('dayone:plugin:remove', ['name' => 'test-plugin'])
        ->expectsConfirmation('Are you sure you want to remove plugin [test-plugin]?', 'yes')
        ->assertSuccessful();

    expect(Plugin::where('name', 'test-plugin')->exists())->toBeFalse();
});

it('fails when plugin is not installed', function (): void {
    $this->artisan('dayone:plugin:remove', ['name' => 'nonexistent'])
        ->assertFailed();
});
