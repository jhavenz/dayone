<?php

declare(strict_types=1);

namespace DayOne\Events;

use DayOne\Models\Product;

final class ConcernAdopted extends DayOneEvent
{
    public readonly string $concern;

    public function __construct(
        Product $product,
        string $concern,
    ) {
        parent::__construct($product);
        $this->concern = $concern;
    }
}
