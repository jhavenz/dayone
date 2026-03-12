<?php

declare(strict_types=1);

use DayOne\DTOs\UsageRecord;

it('can create with all properties', function (): void {
    $recordedAt = new \DateTimeImmutable('2026-03-10T12:00:00Z');

    $record = new UsageRecord(
        subscriptionId: 'sub_123',
        feature: 'api_calls',
        quantity: 150,
        recordedAt: $recordedAt,
    );

    expect($record->subscriptionId)->toBe('sub_123')
        ->and($record->feature)->toBe('api_calls')
        ->and($record->quantity)->toBe(150)
        ->and($record->recordedAt)->toBe($recordedAt);
});

it('prevents external mutation of properties', function (): void {
    $record = new UsageRecord(
        subscriptionId: 'sub_123',
        feature: 'api_calls',
        quantity: 150,
        recordedAt: new \DateTimeImmutable(),
    );

    $record->quantity = 999;
})->throws(\Error::class);
