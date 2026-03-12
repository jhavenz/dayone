<?php

declare(strict_types=1);

namespace DayOne\Exceptions;

use DayOne\Models\Product;

final class ConcernEjectedException extends DayOneException
{
    public static function forConcern(string $concern, Product $product): self
    {
        return new self(
            "The '{$concern}' concern has been ejected for product '{$product->slug}'. "
            . 'Use your own implementation or adopt the concern back via dayone:adopt.',
        );
    }
}
