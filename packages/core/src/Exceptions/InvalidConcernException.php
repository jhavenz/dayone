<?php

declare(strict_types=1);

namespace DayOne\Exceptions;

final class InvalidConcernException extends DayOneException
{
    /**
     * @param array<int, string> $validConcerns
     */
    public static function forConcern(string $concern, array $validConcerns): self
    {
        return new self(
            "Invalid concern '{$concern}'. Valid concerns: " . implode(', ', $validConcerns)
        );
    }
}
