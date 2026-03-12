<?php

declare(strict_types=1);

use DayOne\DTOs\TokenResult;

it('can create with all properties', function (): void {
    $expiresAt = new \DateTimeImmutable('2026-12-31');

    $result = new TokenResult(
        token: 'tok_abc123',
        tokenId: 'tid_456',
        expiresAt: $expiresAt,
    );

    expect($result->token)->toBe('tok_abc123')
        ->and($result->tokenId)->toBe('tid_456')
        ->and($result->expiresAt)->toBe($expiresAt);
});

it('allows null expiresAt', function (): void {
    $result = new TokenResult(
        token: 'tok_abc123',
        tokenId: 'tid_456',
    );

    expect($result->expiresAt)->toBeNull();
});

it('prevents external mutation of properties', function (): void {
    $result = new TokenResult(
        token: 'tok_abc123',
        tokenId: 'tid_456',
    );

    $result->token = 'modified';
})->throws(\Error::class);
