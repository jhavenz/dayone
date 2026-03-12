<?php

declare(strict_types=1);

namespace DayOne\Events;

use DayOne\Models\Product;

final class ConcernEjected extends DayOneEvent
{
    public readonly string $concern;
    public readonly ?string $reason;

    public function __construct(
        Product $product,
        string $concern,
        ?string $reason = null,
    ) {
        parent::__construct($product);
        $this->concern = $concern;
        $this->reason = $reason;
    }
}
