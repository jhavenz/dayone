<?php

declare(strict_types=1);

namespace DayOne\Events;

final class PaymentFailed
{
    public function __construct(
        public readonly string $invoiceId,
        public readonly string $reason,
    ) {}
}
