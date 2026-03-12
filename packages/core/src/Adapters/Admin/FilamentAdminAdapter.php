<?php

declare(strict_types=1);

namespace DayOne\Adapters\Admin;

use DayOne\Concerns\GuardsEjection;
use DayOne\Contracts\Admin\V1\AdminManager;
use DayOne\Models\Product;

final class FilamentAdminAdapter implements AdminManager
{
    use GuardsEjection;

    /** @var array<string, Product> */
    private array $panels = [];

    public function registerPanel(Product $product): void
    {
        $this->guardEjection('admin');
        $this->panels[$product->slug] = $product;
    }

    /** @return array<string, Product> */
    public function getRegisteredPanels(): array
    {
        return $this->panels;
    }
}
