<?php

declare(strict_types=1);

namespace DayOne\Contracts\Auth\V1;

use DayOne\DTOs\TokenResult;
use DayOne\Models\Product;
use Illuminate\Contracts\Auth\Authenticatable;

interface AuthManager
{
    /** @param array<string> $abilities */
    public function issueToken(Authenticatable $user, string $tokenName, array $abilities = []): TokenResult;

    public function revokeToken(Authenticatable $user, string $tokenId): void;

    public function revokeAllTokens(Authenticatable $user): void;

    public function grantProductAccess(Authenticatable $user, Product $product, string $role = 'user'): void;

    public function revokeProductAccess(Authenticatable $user, Product $product): void;

    public function hasProductAccess(Authenticatable $user, Product $product): bool;
}
