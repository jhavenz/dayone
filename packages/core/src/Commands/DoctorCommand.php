<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Models\Product;
use DayOne\Support\ConfigValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class DoctorCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:doctor';

    /** @var string */
    protected $description = 'Full DayOne installation validation';

    private int $passed = 0;

    private int $warnings = 0;

    private int $errors = 0;

    /** @var array<int, class-string> */
    private const V1_CONTRACTS = [
        \DayOne\Contracts\Context\V1\ProductContext::class,
        \DayOne\Contracts\Context\V1\ProductResolver::class,
        \DayOne\Contracts\Auth\V1\AuthManager::class,
        \DayOne\Contracts\Billing\V1\BillingManager::class,
        \DayOne\Contracts\Billing\V1\WebhookHandler::class,
        \DayOne\Contracts\Events\V1\EventManager::class,
        \DayOne\Contracts\Admin\V1\AdminManager::class,
    ];

    public function handle(): int
    {
        $this->line('DayOne Doctor');
        $this->line('=============');
        $this->line('');

        $this->checkPhpVersion();
        $this->checkLaravelVersion();
        $this->checkConfigLoaded();
        $this->checkConfigValid();
        $this->checkMigrations();
        $this->checkContracts();
        $this->checkProducts();
        $this->checkWebhookSecret();
        $this->checkFilament();
        $this->checkScramble();
        $this->checkQueueConnection();
        $this->checkDatabase();

        $this->line('');
        $this->line("Result: {$this->passed} passed, {$this->warnings} warnings, {$this->errors} errors");

        return $this->errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function checkPhpVersion(): void
    {
        $version = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;

        /** @phpstan-ignore greaterOrEqual.alwaysTrue */
        if (PHP_VERSION_ID >= 80400) {
            $this->markPass("PHP {$version}");
        } else {
            $this->markFail("PHP {$version} -- requires >= 8.4");
        }
    }

    private function checkLaravelVersion(): void
    {
        $version = app()->version();
        $major = (int) explode('.', $version)[0];

        if ($major >= 12) {
            $this->markPass("Laravel {$version}");
        } else {
            $this->markFail("Laravel {$version} -- requires >= 12.0");
        }
    }

    private function checkConfigLoaded(): void
    {
        if (config('dayone') !== null) {
            $this->markPass('Config loaded');
        } else {
            $this->markFail('Config not loaded -- run php artisan vendor:publish --tag=dayone-config');
        }
    }

    private function checkConfigValid(): void
    {
        $validator = new ConfigValidator();
        $errors = $validator->validate();

        if ($errors === []) {
            $this->markPass('Config valid');
        } else {
            foreach ($errors as $error) {
                $this->markFail("Config: {$error}");
            }
        }
    }

    private function checkMigrations(): void
    {
        if (Schema::hasTable('dayone_products')) {
            $this->markPass('Migrations current');
        } else {
            $this->markFail('Migrations not run -- run php artisan migrate');
        }
    }

    private function checkContracts(): void
    {
        $bound = 0;
        $total = count(self::V1_CONTRACTS);

        foreach (self::V1_CONTRACTS as $contract) {
            if ($this->laravel->bound($contract)) {
                $bound++;
            }
        }

        if ($bound === $total) {
            $this->markPass("Contracts bound ({$bound}/{$total})");
        } else {
            $this->markFail("Contracts bound ({$bound}/{$total}) -- some V1 contracts are not registered");
        }
    }

    private function checkProducts(): void
    {
        if (! Schema::hasTable('dayone_products')) {
            $this->markWarn('No products found -- migrations not run');
            return;
        }

        if (Product::exists()) {
            $this->markPass('Products found');
        } else {
            $this->markWarn('No products found -- run dayone:product:create');
        }
    }

    private function checkWebhookSecret(): void
    {
        $secret = config('dayone.billing.webhook_secret');

        if (is_string($secret) && $secret !== '') {
            $this->markPass('Stripe webhook secret configured');
        } else {
            $this->markWarn('Stripe webhook secret not set');
        }
    }

    private function checkFilament(): void
    {
        if (class_exists(\Filament\FilamentServiceProvider::class)) {
            $this->markPass('Filament installed');
        } else {
            $this->markInfo('Filament not installed (optional)');
            $this->passed++;
        }
    }

    private function checkScramble(): void
    {
        if (class_exists(\Dedoc\Scramble\Scramble::class)) {
            $this->markPass('Scramble installed');
        } else {
            $this->markInfo('Scramble not installed (optional)');
            $this->passed++;
        }
    }

    private function checkQueueConnection(): void
    {
        /** @var string $connection */
        $connection = config('queue.default', 'sync');

        $this->markPass("Queue connection: {$connection}");
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            $this->markPass('Database connected');
        } catch (\Throwable) {
            $this->markFail('Database connection failed');
        }
    }

    private function markPass(string $message): void
    {
        $this->line("[OK]   {$message}");
        $this->passed++;
    }

    private function markWarn(string $message): void
    {
        $this->line("[WARN] {$message}");
        $this->warnings++;
    }

    private function markFail(string $message): void
    {
        $this->line("[FAIL] {$message}");
        $this->errors++;
    }

    private function markInfo(string $message): void
    {
        $this->line("[INFO] {$message}");
    }
}
