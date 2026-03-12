<?php

declare(strict_types=1);

namespace DayOne\Events;

use Illuminate\Contracts\Auth\Authenticatable;

final class TokenRevoked
{
    public function __construct(
        public readonly Authenticatable $user,
        public readonly string $tokenId,
    ) {}
}
