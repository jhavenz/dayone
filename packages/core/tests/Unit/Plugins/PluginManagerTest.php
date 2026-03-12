<?php

declare(strict_types=1);

use DayOne\Models\Plugin;
use DayOne\Models\Product;
use DayOne\Plugins\PluginManager;
use DayOne\Tests\Fixtures\TestPlugin;

it('can register a plugin', function (): void {
    $manager = app(PluginManager::class);
    $plugin = new TestPlugin();

    $manager->register($plugin);

    expect($manager->isInstalled('test-plugin'))->toBeTrue();
    expect(Plugin::where('name', 'test-plugin')->exists())->toBeTrue();
});

it('boot calls boot on all active plugins', function (): void {
    $manager = app(PluginManager::class);
    $plugin = new TestPlugin();

    $manager->register($plugin);
    $manager->boot();

    expect($plugin->booted)->toBeTrue();
});

it('install creates a database record', function (): void {
    $manager = app(PluginManager::class);

    $manager->install(TestPlugin::class);

    expect(Plugin::where('name', 'test-plugin')->exists())->toBeTrue();
});

it('uninstall removes the database record', function (): void {
    $manager = app(PluginManager::class);

    $manager->install(TestPlugin::class);
    $manager->uninstall('test-plugin');

    expect(Plugin::where('name', 'test-plugin')->exists())->toBeFalse();
});

it('enable toggles is_active to true', function (): void {
    $manager = app(PluginManager::class);
    $manager->install(TestPlugin::class);
    $manager->disable('test-plugin');

    expect(Plugin::where('name', 'test-plugin')->first()->isActive)->toBeFalse();

    $manager->enable('test-plugin');

    expect(Plugin::where('name', 'test-plugin')->first()->isActive)->toBeTrue();
});

it('disable toggles is_active to false', function (): void {
    $manager = app(PluginManager::class);
    $manager->install(TestPlugin::class);

    $manager->disable('test-plugin');

    expect(Plugin::where('name', 'test-plugin')->first()->isActive)->toBeFalse();
});

it('getInstalled returns all plugins', function (): void {
    $manager = app(PluginManager::class);
    $manager->install(TestPlugin::class);

    $installed = $manager->getInstalled();

    expect($installed)->toHaveCount(1);
    expect($installed[0]->name)->toBe('test-plugin');
});

it('getActive returns only active plugins', function (): void {
    $manager = app(PluginManager::class);
    $manager->install(TestPlugin::class);

    expect($manager->getActive())->toHaveCount(1);

    $manager->disable('test-plugin');

    expect($manager->getActive())->toHaveCount(0);
});

it('attachToProduct creates a pivot record', function (): void {
    $manager = app(PluginManager::class);
    $manager->install(TestPlugin::class);

    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'is_active' => true,
    ]);

    $manager->attachToProduct('test-plugin', $product);

    $pluginModel = Plugin::where('name', 'test-plugin')->first();
    expect($pluginModel->products)->toHaveCount(1);
    expect($pluginModel->products->first()->slug)->toBe('test-product');
});

it('detachFromProduct removes the pivot record', function (): void {
    $manager = app(PluginManager::class);
    $manager->install(TestPlugin::class);

    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'is_active' => true,
    ]);

    $manager->attachToProduct('test-plugin', $product);
    $manager->detachFromProduct('test-plugin', $product);

    $pluginModel = Plugin::where('name', 'test-plugin')->first();
    expect($pluginModel->products)->toHaveCount(0);
});
