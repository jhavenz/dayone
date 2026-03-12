<?php

declare(strict_types=1);

namespace DayOne\Contracts\Admin\V1;

use DayOne\Models\Product;

interface AdminManager
{
    public function registerPanel(Product $product): void;

    /** @return array<string, mixed> */
    public function getRegisteredPanels(): array;
}
