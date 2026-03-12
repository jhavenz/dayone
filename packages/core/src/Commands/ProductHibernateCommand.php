<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Events\ProductHibernated;
use DayOne\Models\Product;
use Illuminate\Console\Command;

final class ProductHibernateCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:product:hibernate {slug : The product slug}';

    /** @var string */
    protected $description = 'Hibernate a product (set inactive, reversible)';

    public function handle(): int
    {
        /** @var string $slug */
        $slug = $this->argument('slug');

        $product = Product::where('slug', $slug)->first();

        if (! $product) {
            $this->error("Product with slug '{$slug}' not found.");

            return self::FAILURE;
        }

        $product->update(['is_active' => false]);

        event(new ProductHibernated($product->slug));

        $this->info("[OK] Product '{$product->name}' has been hibernated.");

        return self::SUCCESS;
    }
}
