<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Events\ProductWoken;
use DayOne\Models\Product;
use Illuminate\Console\Command;

final class ProductWakeCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:product:wake {slug : The product slug}';

    /** @var string */
    protected $description = 'Wake a hibernated product (set active)';

    public function handle(): int
    {
        /** @var string $slug */
        $slug = $this->argument('slug');

        $product = Product::where('slug', $slug)->first();

        if (! $product) {
            $this->error("Product with slug '{$slug}' not found.");

            return self::FAILURE;
        }

        $product->update(['is_active' => true]);

        event(new ProductWoken($product->slug));

        $this->info("[OK] Product '{$product->name}' has been woken.");

        return self::SUCCESS;
    }
}
