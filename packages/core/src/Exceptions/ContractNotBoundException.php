<?php

declare(strict_types=1);

namespace DayOne\Exceptions;

final class ContractNotBoundException extends DayOneException
{
    public static function forContract(string $contract): self
    {
        return new self("V1 contract '{$contract}' has no binding in the container.");
    }
}
