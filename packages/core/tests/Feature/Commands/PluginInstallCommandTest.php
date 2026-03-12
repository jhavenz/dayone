<?php

declare(strict_types=1);

use DayOne\Models\Plugin;
use DayOne\Tests\Fixtures\TestPlugin;

it('installs a plugin by class name', function (): void {
    $this->artisan('dayone:plugin:install', ['class' => TestPlugin::class])
        ->assertSuccessful();

    expect(Plugin::where('name', 'test-plugin')->exists())->toBeTrue();
});

it('fails when plugin class does not exist', function (): void {
    $this->artisan('dayone:plugin:install', ['class' => 'App\\Plugins\\NonExistent'])
        ->assertFailed();
});
