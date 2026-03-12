<?php

declare(strict_types=1);

namespace DayOne\Events;

use DayOne\Models\Product;

final class SubscriptionCanceled
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly ?Product $product = null,
    ) {}
}
