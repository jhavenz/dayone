<?php

declare(strict_types=1);

namespace DayOne\Events;

final class SubscriptionExpired
{
    public function __construct(
        public readonly string $subscriptionId,
    ) {}
}
