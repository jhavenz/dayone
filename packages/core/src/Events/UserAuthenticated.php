<?php

declare(strict_types=1);

namespace DayOne\Events;

use DayOne\Models\Product;
use Illuminate\Contracts\Auth\Authenticatable;

final class UserAuthenticated
{
    public function __construct(
        public readonly Authenticatable $user,
        public readonly ?Product $product = null,
    ) {}
}
