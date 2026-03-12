<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Plugins\PluginManager;
use Illuminate\Console\Command;

final class PluginRemoveCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:plugin:remove {name : The plugin name}';

    /** @var string */
    protected $description = 'Remove an installed DayOne plugin';

    public function handle(PluginManager $manager): int
    {
        /** @var string $name */
        $name = $this->argument('name');

        if (! $manager->isInstalled($name)) {
            $this->error("Plugin [{$name}] is not installed.");

            return self::FAILURE;
        }

        if (! $this->confirm("Are you sure you want to remove plugin [{$name}]?")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $manager->uninstall($name);

        $this->info("Plugin [{$name}] removed successfully.");

        return self::SUCCESS;
    }
}
