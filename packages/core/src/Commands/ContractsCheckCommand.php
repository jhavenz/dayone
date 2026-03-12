<?php

declare(strict_types=1);

namespace DayOne\Commands;

use Illuminate\Console\Command;

final class ContractsCheckCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:contracts:check';

    /** @var string */
    protected $description = 'Check that all V1 contracts have bound implementations';

    /** @var array<string> */
    private const array CONTRACTS = [
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
        $this->info('Checking V1 contract bindings...');
        $this->info('');

        $hasUnbound = false;
        $rows = [];

        foreach (self::CONTRACTS as $contract) {
            $bound = $this->laravel->bound($contract);

            if (! $bound) {
                $hasUnbound = true;
            }

            $implementation = $bound
                ? get_class($this->laravel->make($contract))
                : 'NOT BOUND';

            $status = $bound ? '[OK]' : '[FAIL]';

            $rows[] = [$status, $this->shortName($contract), $implementation];
        }

        $this->table(['Status', 'Contract', 'Implementation'], $rows);

        if ($hasUnbound) {
            $this->error('[FAIL] Some contracts are not bound.');

            return self::FAILURE;
        }

        $this->info('[OK] All contracts are bound.');

        return self::SUCCESS;
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
