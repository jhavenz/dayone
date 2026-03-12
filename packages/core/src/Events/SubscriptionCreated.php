<?php

declare(strict_types=1);

namespace DayOne\Events;

use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Product;
use Illuminate\Contracts\Auth\Authenticatable;

final class SubscriptionCreated
{
    public function __construct(
        public readonly Authenticatable $user,
        public readonly Product $product,
        public readonly string $subscriptionId,
        public readonly SubscriptionStatus $status,
    ) {}
}
