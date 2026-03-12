<?php

declare(strict_types=1);

namespace DayOne\Events;

final class ProductWoken extends DayOneEvent
{
    public function __construct(string $productSlug)
    {
        parent::__construct($productSlug);
    }
}
