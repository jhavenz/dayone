<?php

declare(strict_types=1);

use DayOne\DTOs\SubscriptionStatus;

it('returns isUsable true for active statuses', function (SubscriptionStatus $status): void {
    expect($status->isUsable())->toBeTrue();
})->with([
    'active' => SubscriptionStatus::Active,
    'trialing' => SubscriptionStatus::Trialing,
    'past_due' => SubscriptionStatus::PastDue,
]);

it('returns isUsable false for non-active statuses', function (SubscriptionStatus $status): void {
    expect($status->isUsable())->toBeFalse();
})->with([
    'paused' => SubscriptionStatus::Paused,
    'canceled' => SubscriptionStatus::Canceled,
    'expired' => SubscriptionStatus::Expired,
    'incomplete' => SubscriptionStatus::Incomplete,
    'incomplete_expired' => SubscriptionStatus::IncompleteExpired,
]);
