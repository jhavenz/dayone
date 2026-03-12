<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Models\Plugin;
use DayOne\Plugins\PluginManager;
use Illuminate\Console\Command;

final class PluginListCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:plugin:list';

    /** @var string */
    protected $description = 'List all installed DayOne plugins';

    public function handle(PluginManager $manager): int
    {
        $plugins = $manager->getInstalled();

        if ($plugins === []) {
            $this->info('No plugins installed.');

            return self::SUCCESS;
        }

        $rows = array_map(function (Plugin $plugin): array {
            $productNames = $plugin->products->pluck('name')->implode(', ') ?: '(none)';

            return [
                $plugin->name,
                $plugin->version,
                $plugin->isActive ? 'active' : 'disabled',
                $productNames,
            ];
        }, $plugins);

        $this->table(
            ['Name', 'Version', 'Status', 'Products'],
            $rows,
        );

        return self::SUCCESS;
    }
}
