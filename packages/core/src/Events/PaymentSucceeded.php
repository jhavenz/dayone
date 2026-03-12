<?php

declare(strict_types=1);

namespace DayOne\Events;

final class PaymentSucceeded
{
    public function __construct(
        public readonly string $invoiceId,
        public readonly int $amountInCents,
        public readonly string $currency,
    ) {}
}
