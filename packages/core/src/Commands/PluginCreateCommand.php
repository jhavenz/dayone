<?php

declare(strict_types=1);

namespace DayOne\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class PluginCreateCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:plugin:create {name : The plugin name}';

    /** @var string */
    protected $description = 'Scaffold a new DayOne plugin class';

    public function handle(): int
    {
        /** @var string $name */
        $name = $this->argument('name');
        $className = Str::studly($name) . 'Plugin';
        $slug = Str::slug($name);

        $stub = $this->buildStub($className, $slug);

        $this->info("Plugin skeleton for [{$className}]:");
        $this->newLine();
        $this->line($stub);

        return self::SUCCESS;
    }

    private function buildStub(string $className, string $slug): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Plugins;

        use DayOne\Contracts\Plugin;

        final class {$className} implements Plugin
        {
            public function name(): string
            {
                return '{$slug}';
            }

            public function version(): string
            {
                return '1.0.0';
            }

            public function install(): void
            {
            }

            public function uninstall(): void
            {
            }

            public function boot(): void
            {
            }
        }
        PHP;
    }
}
