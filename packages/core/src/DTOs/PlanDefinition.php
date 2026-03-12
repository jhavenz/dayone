<?php

declare(strict_types=1);

namespace DayOne\DTOs;

final class PlanDefinition
{
    public bool $isFree {
        get => $this->priceInCents === 0;
    }

    /**
     * @param array<string, mixed> $features
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $priceInCents,
        public readonly string $currency,
        public readonly string $interval,
        public readonly array $features = [],
    ) {}
}
