<?php

declare(strict_types=1);

namespace DayOne\DTOs;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Trialing = 'trialing';
    case PastDue = 'past_due';
    case Paused = 'paused';
    case Canceled = 'canceled';
    case Expired = 'expired';
    case Incomplete = 'incomplete';
    case IncompleteExpired = 'incomplete_expired';

    public function isUsable(): bool
    {
        return match ($this) {
            self::Active, self::Trialing, self::PastDue => true,
            default => false,
        };
    }
}
