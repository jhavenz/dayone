<?php

declare(strict_types=1);

namespace DayOne\Commands;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:install';

    /** @var string */
    protected $description = 'Install the DayOne package';

    public function handle(): int
    {
        $this->info('Installing DayOne...');

        $this->call('vendor:publish', [
            '--tag' => 'dayone-config',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'dayone-migrations',
        ]);

        $this->call('migrate');

        $this->info('[OK] DayOne installed successfully.');

        return self::SUCCESS;
    }
}
