<?php

declare(strict_types=1);

namespace DayOne\Ejection;

use DayOne\Events\ConcernAdopted;
use DayOne\Events\ConcernEjected;
use DayOne\Exceptions\InvalidConcernException;
use DayOne\Models\Ejection;
use DayOne\Models\Product;
use Illuminate\Support\Carbon;

final class EjectionManager
{
    /** @return array<int, string> */
    private function validConcerns(): array
    {
        /** @var array<int, string> $concerns */
        $concerns = config('dayone.ejection.concerns', ['billing', 'auth', 'admin']);

        return $concerns;
    }

    private function validateConcern(string $concern): void
    {
        if (! in_array($concern, $this->validConcerns(), true)) {
            throw InvalidConcernException::forConcern($concern, $this->validConcerns());
        }
    }

    public function eject(Product $product, string $concern, ?string $reason = null): void
    {
        $this->validateConcern($concern);

        Ejection::withoutGlobalScopes()->updateOrCreate(
            [
                'product_id' => $product->id,
                'concern' => $concern,
            ],
            [
                'ejected_at' => Carbon::now(),
                'metadata' => $reason ? ['reason' => $reason] : null,
            ],
        );

        event(new ConcernEjected($product, $concern, $reason));
    }

    public function adopt(Product $product, string $concern): void
    {
        $this->validateConcern($concern);

        Ejection::withoutGlobalScopes()
            ->where('product_id', $product->id)
            ->where('concern', $concern)
            ->delete();

        event(new ConcernAdopted($product, $concern));
    }

    public function isEjected(Product $product, string $concern): bool
    {
        $this->validateConcern($concern);

        return Ejection::withoutGlobalScopes()
            ->where('product_id', $product->id)
            ->where('concern', $concern)
            ->exists();
    }

    /** @return array<int, string> */
    public function getEjections(Product $product): array
    {
        return Ejection::withoutGlobalScopes()
            ->where('product_id', $product->id)
            ->pluck('concern')
            ->all();
    }

    /** @return array<int, Product> */
    public function getEjectedProducts(string $concern): array
    {
        $this->validateConcern($concern);

        $productIds = Ejection::withoutGlobalScopes()
            ->where('concern', $concern)
            ->pluck('product_id')
            ->all();

        return Product::whereIn('id', $productIds)->get()->all();
    }

    /** @return array<int, Ejection> */
    public function getAllEjections(): array
    {
        return Ejection::withoutGlobalScopes()->with('product')->get()->all();
    }
}
