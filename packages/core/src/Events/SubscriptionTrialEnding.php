<?php

declare(strict_types=1);

namespace DayOne\Events;

final class SubscriptionTrialEnding
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly \DateTimeImmutable $trialEndsAt,
    ) {}
}
