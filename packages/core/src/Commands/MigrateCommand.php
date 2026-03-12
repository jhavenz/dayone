<?php

declare(strict_types=1);

namespace DayOne\Commands;

use Illuminate\Console\Command;

final class MigrateCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:migrate';

    /** @var string */
    protected $description = 'Run DayOne package migrations';

    public function handle(): int
    {
        $this->info('Running DayOne migrations...');

        $paths = [
            dirname(__DIR__, 2) . '/database/migrations',
        ];

        $this->call('migrate', [
            '--path' => $paths,
            '--realpath' => true,
        ]);

        $this->info('[OK] DayOne migrations complete.');

        return self::SUCCESS;
    }
}
