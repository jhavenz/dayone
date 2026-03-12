<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Product;
use DayOne\Models\Subscription;
use Illuminate\Console\Command;

final class BillingReportCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:billing:report';

    /** @var string */
    protected $description = 'Show billing subscription report';

    public function handle(): int
    {
        $this->info('Billing Report');
        $this->info('==============');

        $statusCounts = Subscription::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        if (empty($statusCounts)) {
            $this->info('No subscriptions found.');

            return self::SUCCESS;
        }

        $this->info('');
        $this->info('Status Breakdown:');

        $statusRows = [];
        foreach ($statusCounts as $status => $count) {
            $statusRows[] = [$status, (string) $count];
        }

        $this->table(['Status', 'Count'], $statusRows);

        $this->info('');
        $this->info('Per-Product Breakdown:');

        $products = Product::all();

        $productRows = [];
        foreach ($products as $product) {
            foreach (SubscriptionStatus::cases() as $status) {
                $count = Subscription::where('product_id', $product->id)
                    ->where('status', $status->value)
                    ->count();

                if ($count > 0) {
                    $productRows[] = [$product->name, $status->value, (string) $count];
                }
            }
        }

        if (empty($productRows)) {
            $this->info('No per-product data available.');
        } else {
            $this->table(['Product', 'Status', 'Count'], $productRows);
        }

        return self::SUCCESS;
    }
}
