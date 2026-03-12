<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Ejection\EjectionManager;
use DayOne\Exceptions\InvalidConcernException;
use DayOne\Exceptions\ProductNotFoundException;
use DayOne\Models\Product;
use Illuminate\Console\Command;

final class AdoptCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:adopt {slug : The product slug} {concern : The concern to adopt (billing, auth, admin)}';

    /** @var string */
    protected $description = 'Adopt a product back into a DayOne-managed concern';

    public function handle(EjectionManager $manager): int
    {
        /** @var string $slug */
        $slug = $this->argument('slug');

        /** @var string $concern */
        $concern = $this->argument('concern');

        $product = Product::where('slug', $slug)->first();

        if (! $product) {
            $this->error(ProductNotFoundException::forSlug($slug)->getMessage());

            return self::FAILURE;
        }

        try {
            $manager->adopt($product, $concern);
        } catch (InvalidConcernException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("[OK] Product '{$product->name}' adopted back into '{$concern}'.");

        return self::SUCCESS;
    }
}
