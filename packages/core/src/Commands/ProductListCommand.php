<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Models\Product;
use DayOne\Models\Subscription;
use Illuminate\Console\Command;

final class ProductListCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:product:list';

    /** @var string */
    protected $description = 'List all DayOne products';

    public function handle(): int
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->info('No products found.');

            return self::SUCCESS;
        }

        $rows = $products->map(function (Product $product): array {
            $subscriberCount = Subscription::where('product_id', $product->id)
                ->where('status', 'active')
                ->count();

            return [
                $product->slug,
                $product->name,
                $product->isActive ? 'active' : 'inactive',
                (string) $subscriberCount,
                (string) $subscriberCount,
            ];
        })->all();

        $this->table(
            ['Slug', 'Name', 'Status', 'Subscribers', 'MRR'],
            $rows,
        );

        return self::SUCCESS;
    }
}
