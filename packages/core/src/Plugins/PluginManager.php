<?php

declare(strict_types=1);

namespace DayOne\Plugins;

use DayOne\Contracts\Plugin;
use DayOne\Models\Plugin as PluginModel;
use DayOne\Models\Product;

final class PluginManager
{
    /** @var array<string, Plugin> */
    private array $plugins = [];

    /**
     * Register a plugin in memory and persist to the database
     * if no record exists yet.
     */
    public function register(Plugin $plugin): void
    {
        $this->plugins[$plugin->name()] = $plugin;

        PluginModel::firstOrCreate(
            ['name' => $plugin->name()],
            [
                'version' => $plugin->version(),
                'is_active' => true,
                'installed_at' => now(),
            ],
        );
    }

    /**
     * Boot every registered plugin whose database record is active.
     */
    public function boot(): void
    {
        foreach ($this->plugins as $name => $plugin) {
            $record = PluginModel::where('name', $name)->first();

            if ($record instanceof PluginModel && $record->isActive) {
                $plugin->boot();
            }
        }
    }

    /**
     * Instantiate a plugin class, run its install hook, and register it.
     */
    public function install(string $pluginClass): void
    {
        /** @var Plugin $plugin */
        $plugin = app($pluginClass);
        $plugin->install();
        $this->register($plugin);
    }

    /**
     * Run the uninstall hook and remove the database record.
     */
    public function uninstall(string $name): void
    {
        if (isset($this->plugins[$name])) {
            $this->plugins[$name]->uninstall();
            unset($this->plugins[$name]);
        }

        PluginModel::where('name', $name)->delete();
    }

    /**
     * Mark a plugin as active in the database.
     */
    public function enable(string $name): void
    {
        PluginModel::where('name', $name)->update(['is_active' => true]);
    }

    /**
     * Mark a plugin as inactive in the database.
     */
    public function disable(string $name): void
    {
        PluginModel::where('name', $name)->update(['is_active' => false]);
    }

    /**
     * Return all installed plugin records.
     *
     * @return array<int, PluginModel>
     */
    public function getInstalled(): array
    {
        return PluginModel::all()->all();
    }

    /**
     * Return only active plugin records.
     *
     * @return array<int, PluginModel>
     */
    public function getActive(): array
    {
        return PluginModel::where('is_active', true)->get()->all();
    }

    /**
     * Check whether a plugin is installed by name.
     */
    public function isInstalled(string $name): bool
    {
        return PluginModel::where('name', $name)->exists();
    }

    /**
     * Attach a plugin to a product via the pivot table.
     */
    public function attachToProduct(string $pluginName, Product $product): void
    {
        $plugin = PluginModel::where('name', $pluginName)->firstOrFail();
        $plugin->products()->syncWithoutDetaching([$product->id]);
    }

    /**
     * Detach a plugin from a product.
     */
    public function detachFromProduct(string $pluginName, Product $product): void
    {
        $plugin = PluginModel::where('name', $pluginName)->firstOrFail();
        $plugin->products()->detach($product->id);
    }

    /**
     * Return all plugin records attached to a product.
     *
     * @return array<int, PluginModel>
     */
    public function getProductPlugins(Product $product): array
    {
        return PluginModel::whereHas('products', function ($query) use ($product): void {
            $query->where('dayone_plugin_products.product_id', $product->id);
        })->get()->all();
    }
}
