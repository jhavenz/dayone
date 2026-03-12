<?php

declare(strict_types=1);

use DayOne\Contracts\Plugin;
use DayOne\Models\Product;
use DayOne\Plugins\PluginManager;
use Tests\Traits\InteractsWithDayOne;

uses(InteractsWithDayOne::class);

it('registers and boots a plugin', function () {
    $manager = app(PluginManager::class);

    $plugin = new TestBootPlugin();
    $manager->register($plugin);
    $manager->boot();

    expect($plugin->wasBooted())->toBeTrue();
});

it('installs a plugin and persists to database', function () {
    $this->app->bind(TestInstallablePlugin::class);

    $manager = app(PluginManager::class);
    $manager->install(TestInstallablePlugin::class);

    $this->assertDatabaseHas('dayone_plugins', [
        'name' => 'installable-plugin',
        'is_active' => true,
    ]);
});

it('uninstalls a plugin', function () {
    $this->app->bind(TestInstallablePlugin::class);

    $manager = app(PluginManager::class);
    $manager->install(TestInstallablePlugin::class);
    $manager->uninstall('installable-plugin');

    $this->assertDatabaseMissing('dayone_plugins', [
        'name' => 'installable-plugin',
    ]);
});

it('attaches plugin to a product', function () {
    $product = $this->seedProduct('Acme', 'acme');
    $this->app->bind(TestInstallablePlugin::class);

    $manager = app(PluginManager::class);
    $manager->install(TestInstallablePlugin::class);
    $manager->attachToProduct('installable-plugin', $product);

    $this->assertDatabaseHas('dayone_plugin_products', [
        'product_id' => $product->id,
    ]);
});

it('checks if plugin is installed', function () {
    $this->app->bind(TestInstallablePlugin::class);

    $manager = app(PluginManager::class);

    expect($manager->isInstalled('installable-plugin'))->toBeFalse();

    $manager->install(TestInstallablePlugin::class);

    expect($manager->isInstalled('installable-plugin'))->toBeTrue();
});

// Test plugin implementations

class TestBootPlugin implements Plugin
{
    private bool $booted = false;

    public function name(): string { return 'boot-test'; }
    public function version(): string { return '1.0.0'; }
    public function install(): void {}
    public function uninstall(): void {}
    public function boot(): void { $this->booted = true; }
    public function wasBooted(): bool { return $this->booted; }
}

class TestInstallablePlugin implements Plugin
{
    public function name(): string { return 'installable-plugin'; }
    public function version(): string { return '1.0.0'; }
    public function install(): void {}
    public function uninstall(): void {}
    public function boot(): void {}
}
