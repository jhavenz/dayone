<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Contracts\Billing\V1\BillingManager;
use Illuminate\Console\Command;

final class BillingSyncCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:billing:sync';

    /** @var string */
    protected $description = 'Sync billing plans from the provider';

    public function handle(): int
    {
        try {
            /** @var BillingManager $billing */
            $billing = app(BillingManager::class);
        } catch (\Throwable) {
            $this->error('[FAIL] BillingManager is not bound in the container. Ensure DayOneServiceProvider is registered.');

            return self::FAILURE;
        }

        $this->info('Syncing billing plans...');

        try {
            $billing->syncPlans();
            $this->info('[OK] Billing plans synced successfully.');
        } catch (\Throwable $e) {
            $this->error("[FAIL] Sync failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
