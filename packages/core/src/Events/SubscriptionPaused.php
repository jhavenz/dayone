<?php

declare(strict_types=1);

namespace DayOne\Events;

final class SubscriptionPaused
{
    public function __construct(
        public readonly string $subscriptionId,
    ) {}
}
