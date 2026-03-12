<?php

declare(strict_types=1);

namespace DayOne\Events;

use Illuminate\Contracts\Auth\Authenticatable;

final class TokenIssued
{
    public function __construct(
        public readonly Authenticatable $user,
        public readonly string $tokenId,
        public readonly ?string $productSlug = null,
    ) {}
}
