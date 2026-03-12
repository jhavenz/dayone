<?php

declare(strict_types=1);

namespace DayOne\Concerns;

use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\Models\Product;
use DayOne\Runtime\ProductContextInstance;

/**
 * Serializes the current product context for queued jobs so the
 * product scope is restored when the job executes on a worker.
 */
trait ProductAware
{
    public ?string $productId = null;

    public function initializeProductAware(): void
    {
        $context = app(ProductContext::class);

        if ($context->hasProduct()) {
            $this->productId = $context->product()?->id;
        }
    }

    public function restoreProductContext(): void
    {
        if ($this->productId === null) {
            return;
        }

        $product = Product::query()->find($this->productId);

        if (! $product instanceof Product) {
            return;
        }

        $context = app(ProductContextInstance::class);
        $context->setProduct($product);
    }
}
