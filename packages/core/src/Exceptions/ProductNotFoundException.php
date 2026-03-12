<?php

declare(strict_types=1);

namespace DayOne\Exceptions;

final class ProductNotFoundException extends DayOneException
{
    public static function forSlug(string $slug): self
    {
        return new self("Product '{$slug}' not found.");
    }
}
