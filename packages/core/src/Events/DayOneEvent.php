<?php

declare(strict_types=1);

namespace DayOne\Events;

use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\Models\Product;

abstract class DayOneEvent
{
    public readonly ?string $productSlug;

    public function __construct(Product|string|null $product = null)
    {
        $this->productSlug = match (true) {
            $product instanceof Product => $product->slug,
            is_string($product) => $product,
            default => $this->resolveSlugFromContext(),
        };
    }

    private function resolveSlugFromContext(): ?string
    {
        try {
            $context = app(ProductContext::class);

            return $context->hasProduct() ? $context->product()?->slug : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
