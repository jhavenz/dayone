<?php

declare(strict_types=1);

namespace DayOne\DTOs;

final class TokenResult
{
    public function __construct(
        public private(set) string $token,
        public private(set) string $tokenId,
        public private(set) ?\DateTimeImmutable $expiresAt = null,
    ) {}
}
