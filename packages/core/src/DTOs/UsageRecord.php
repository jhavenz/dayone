<?php

declare(strict_types=1);

namespace DayOne\DTOs;

final class UsageRecord
{
    public function __construct(
        public private(set) string $subscriptionId,
        public private(set) string $feature,
        public private(set) int $quantity,
        public private(set) \DateTimeImmutable $recordedAt,
    ) {}
}
