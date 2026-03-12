<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Ejection\EjectionManager;
use DayOne\Exceptions\InvalidConcernException;
use DayOne\Exceptions\ProductNotFoundException;
use DayOne\Models\Product;
use Illuminate\Console\Command;

final class EjectCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:eject {slug : The product slug} {concern : The concern to eject (billing, auth, admin)}';

    /** @var string */
    protected $description = 'Eject a product from a DayOne-managed concern';

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
            $manager->eject($product, $concern);
        } catch (InvalidConcernException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("[OK] Product '{$product->name}' ejected from '{$concern}'.");
        $this->line("The product is now responsible for its own {$concern} implementation.");

        return self::SUCCESS;
    }
}
