<?php

declare(strict_types=1);

use DayOne\DTOs\PlanDefinition;

it('returns isFree true when priceInCents is zero', function (): void {
    $plan = new PlanDefinition(
        id: 'plan_free',
        name: 'Free',
        priceInCents: 0,
        currency: 'usd',
        interval: 'month',
    );

    expect($plan->isFree)->toBeTrue();
});

it('returns isFree false when priceInCents is greater than zero', function (): void {
    $plan = new PlanDefinition(
        id: 'plan_pro',
        name: 'Pro',
        priceInCents: 1999,
        currency: 'usd',
        interval: 'month',
        features: ['feature_a' => true],
    );

    expect($plan->isFree)->toBeFalse();
});

it('stores all constructor properties', function (): void {
    $plan = new PlanDefinition(
        id: 'plan_test',
        name: 'Test Plan',
        priceInCents: 500,
        currency: 'eur',
        interval: 'year',
        features: ['seats' => 10],
    );

    expect($plan->id)->toBe('plan_test')
        ->and($plan->name)->toBe('Test Plan')
        ->and($plan->priceInCents)->toBe(500)
        ->and($plan->currency)->toBe('eur')
        ->and($plan->interval)->toBe('year')
        ->and($plan->features)->toBe(['seats' => 10]);
});
