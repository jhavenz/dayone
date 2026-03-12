<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Plugins\PluginManager;
use Illuminate\Console\Command;

final class PluginInstallCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:plugin:install {class : The fully-qualified plugin class name}';

    /** @var string */
    protected $description = 'Install a DayOne plugin';

    public function handle(PluginManager $manager): int
    {
        /** @var string $class */
        $class = $this->argument('class');

        if (! class_exists($class)) {
            $this->error("Plugin class [{$class}] not found.");

            return self::FAILURE;
        }

        $manager->install($class);

        $this->info("Plugin [{$class}] installed successfully.");

        return self::SUCCESS;
    }
}
