<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Models\Product;
use DayOne\Support\ConfigValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class StatusCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:status';

    /** @var string */
    protected $description = 'Quick DayOne health check';

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
        $passed = 0;
        $warnings = 0;
        $total = 0;

        $checks = [
            $this->checkDatabase(),
            $this->checkConfig(),
            $this->checkConfigValid(),
            $this->checkMigrations(),
            $this->checkContracts(),
            $this->checkProducts(),
            $this->checkWebhookSecret(),
        ];

        foreach ($checks as $result) {
            $total++;
            if ($result === 'pass') {
                $passed++;
            } elseif ($result === 'warn') {
                $passed++;
                $warnings++;
            }
        }

        $errors = $total - $passed;

        if ($errors > 0) {
            $this->line("DayOne Status: FAIL ({$passed}/{$total} checks passed, {$warnings} warnings, {$errors} errors)");

            return self::FAILURE;
        }

        if ($warnings > 0) {
            $this->line("DayOne Status: OK ({$passed}/{$total} checks passed, {$warnings} warnings)");

            return self::SUCCESS;
        }

        $this->line("DayOne Status: OK ({$passed}/{$total} checks passed)");

        return self::SUCCESS;
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();
            return 'pass';
        } catch (\Throwable) {
            return 'fail';
        }
    }

    private function checkConfig(): string
    {
        return config('dayone') !== null ? 'pass' : 'fail';
    }

    private function checkConfigValid(): string
    {
        $validator = new ConfigValidator();

        return $validator->validate() === [] ? 'pass' : 'fail';
    }

    private function checkMigrations(): string
    {
        return Schema::hasTable('dayone_products') ? 'pass' : 'fail';
    }

    private function checkContracts(): string
    {
        foreach (self::V1_CONTRACTS as $contract) {
            if (! $this->laravel->bound($contract)) {
                return 'fail';
            }
        }

        return 'pass';
    }

    private function checkProducts(): string
    {
        if (! Schema::hasTable('dayone_products')) {
            return 'warn';
        }

        return Product::exists() ? 'pass' : 'warn';
    }

    private function checkWebhookSecret(): string
    {
        $secret = config('dayone.billing.webhook_secret');

        return is_string($secret) && $secret !== '' ? 'pass' : 'warn';
    }
}
