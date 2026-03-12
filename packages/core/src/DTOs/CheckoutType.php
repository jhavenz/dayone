<?php

declare(strict_types=1);

namespace DayOne\DTOs;

enum CheckoutType: string
{
    case Subscription = 'subscription';
    case OneTime = 'one_time';
    case Setup = 'setup';
}
