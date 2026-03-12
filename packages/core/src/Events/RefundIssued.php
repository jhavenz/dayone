<?php

declare(strict_types=1);

namespace DayOne\Events;

final class RefundIssued
{
    public function __construct(
        public readonly string $chargeId,
        public readonly int $amountInCents,
        public readonly string $currency,
    ) {}
}
