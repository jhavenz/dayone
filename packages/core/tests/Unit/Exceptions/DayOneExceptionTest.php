<?php

declare(strict_types=1);

use DayOne\Exceptions\ContractNotBoundException;
use DayOne\Exceptions\DayOneException;
use DayOne\Exceptions\InvalidConcernException;
use DayOne\Exceptions\ProductNotFoundException;

it('ProductNotFoundException has correct message from slug', function (): void {
    $exception = ProductNotFoundException::forSlug('acme-app');

    expect($exception)->toBeInstanceOf(DayOneException::class)
        ->and($exception->getMessage())->toBe("Product 'acme-app' not found.");
});

it('ContractNotBoundException has correct message', function (): void {
    $exception = ContractNotBoundException::forContract('DayOne\Contracts\Billing\V1\BillingManager');

    expect($exception)->toBeInstanceOf(DayOneException::class)
        ->and($exception->getMessage())->toBe("V1 contract 'DayOne\Contracts\Billing\V1\BillingManager' has no binding in the container.");
});

it('InvalidConcernException lists valid concerns', function (): void {
    $exception = InvalidConcernException::forConcern('invalid', ['billing', 'auth', 'admin']);

    expect($exception)->toBeInstanceOf(DayOneException::class)
        ->and($exception->getMessage())->toBe("Invalid concern 'invalid'. Valid concerns: billing, auth, admin");
});

it('all custom exceptions extend DayOneException', function (): void {
    expect(new ProductNotFoundException('test'))->toBeInstanceOf(DayOneException::class)
        ->and(new ContractNotBoundException('test'))->toBeInstanceOf(DayOneException::class)
        ->and(new InvalidConcernException('test'))->toBeInstanceOf(DayOneException::class);
});

it('DayOneException extends RuntimeException', function (): void {
    $exception = new DayOneException('test');

    expect($exception)->toBeInstanceOf(\RuntimeException::class);
});
