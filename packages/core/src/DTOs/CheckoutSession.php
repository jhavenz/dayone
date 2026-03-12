<?php

declare(strict_types=1);

namespace DayOne\DTOs;

final class CheckoutSession
{
    public bool $isComplete {
        get => $this->status === 'complete';
    }

    public bool $requiresRedirect {
        get => $this->url !== '' && !$this->isComplete;
    }

    public function __construct(
        public readonly string $id,
        public readonly string $url,
        public readonly string $status,
        public readonly ?string $customerId = null,
    ) {}
}
