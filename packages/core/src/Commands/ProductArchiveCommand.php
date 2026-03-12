<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Events\ProductArchived;
use DayOne\Models\Product;
use Illuminate\Console\Command;

final class ProductArchiveCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:product:archive {slug : The product slug}';

    /** @var string */
    protected $description = 'Archive a product (permanent deactivation intent)';

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

        event(new ProductArchived($product->slug));

        $this->warn("[WARN] Product '{$product->name}' has been archived. This indicates permanent deactivation intent, unlike hibernate which is reversible.");
        $this->info("[OK] Product archived successfully.");

        return self::SUCCESS;
    }
}
