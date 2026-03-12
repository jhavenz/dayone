<?php

declare(strict_types=1);

namespace DayOne\Events;

use DayOne\DTOs\SubscriptionStatus;

final class SubscriptionUpdated
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly SubscriptionStatus $oldStatus,
        public readonly SubscriptionStatus $newStatus,
    ) {}
}
