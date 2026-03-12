<?php

declare(strict_types=1);

namespace DayOne\Contracts\Events\V1;

interface EventManager
{
    public function dispatch(object $event): void;

    public function registerProductListener(string $productSlug, string $event, string $listener): void;

    /** @return array<string, array<string>> */
    public function getProductListeners(string $productSlug): array;
}
