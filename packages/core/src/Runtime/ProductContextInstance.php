<?php

declare(strict_types=1);

namespace DayOne\Runtime;

use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\Exceptions\ProductNotFoundException;
use DayOne\Models\Product;

final class ProductContextInstance implements ProductContext
{
    private(set) ?Product $resolved = null;

    public function product(): ?Product
    {
        return $this->resolved;
    }

    public function hasProduct(): bool
    {
        return $this->resolved !== null;
    }

    public function requireProduct(): Product
    {
        return $this->resolved ?? throw new ProductNotFoundException(
            'No product context resolved. Ensure the dayone.product middleware is applied.',
        );
    }

    public function setProduct(Product $product): void
    {
        $this->resolved = $product;
    }
}
