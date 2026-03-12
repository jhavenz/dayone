<?php

declare(strict_types=1);

namespace DayOne\Events;

final class ProductCreated extends DayOneEvent
{
    public readonly string $productName;

    public function __construct(
        string $productSlug,
        string $productName,
    ) {
        parent::__construct($productSlug);
        $this->productName = $productName;
    }
}
