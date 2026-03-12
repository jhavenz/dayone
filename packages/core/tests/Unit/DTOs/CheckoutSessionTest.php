<?php

declare(strict_types=1);

use DayOne\DTOs\CheckoutSession;

it('returns isComplete true when status is complete', function (): void {
    $session = new CheckoutSession(
        id: 'cs_123',
        url: 'https://checkout.example.com',
        status: 'complete',
    );

    expect($session->isComplete)->toBeTrue();
});

it('returns isComplete false when status is not complete', function (): void {
    $session = new CheckoutSession(
        id: 'cs_123',
        url: 'https://checkout.example.com',
        status: 'open',
    );

    expect($session->isComplete)->toBeFalse();
});

it('returns requiresRedirect true when url is set and not complete', function (): void {
    $session = new CheckoutSession(
        id: 'cs_123',
        url: 'https://checkout.example.com',
        status: 'open',
    );

    expect($session->requiresRedirect)->toBeTrue();
});

it('returns requiresRedirect false when status is complete', function (): void {
    $session = new CheckoutSession(
        id: 'cs_123',
        url: 'https://checkout.example.com',
        status: 'complete',
    );

    expect($session->requiresRedirect)->toBeFalse();
});

it('returns requiresRedirect false when url is empty', function (): void {
    $session = new CheckoutSession(
        id: 'cs_123',
        url: '',
        status: 'open',
    );

    expect($session->requiresRedirect)->toBeFalse();
});

it('stores customerId as nullable', function (): void {
    $withCustomer = new CheckoutSession(
        id: 'cs_123',
        url: '',
        status: 'open',
        customerId: 'cus_456',
    );

    $withoutCustomer = new CheckoutSession(
        id: 'cs_124',
        url: '',
        status: 'open',
    );

    expect($withCustomer->customerId)->toBe('cus_456')
        ->and($withoutCustomer->customerId)->toBeNull();
});
