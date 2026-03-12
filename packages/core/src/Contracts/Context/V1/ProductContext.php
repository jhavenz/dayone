<?php

declare(strict_types=1);

namespace DayOne\Contracts\Context\V1;

use DayOne\Models\Product;

interface ProductContext
{
    public function product(): ?Product;

    public function hasProduct(): bool;

    /**
     * @throws \RuntimeException when no product is resolved
     */
    public function requireProduct(): Product;
}
