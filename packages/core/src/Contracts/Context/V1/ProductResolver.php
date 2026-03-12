<?php

declare(strict_types=1);

namespace DayOne\Contracts\Context\V1;

use DayOne\Models\Product;
use Illuminate\Http\Request;

interface ProductResolver
{
    public function resolve(Request $request): ?Product;
}
