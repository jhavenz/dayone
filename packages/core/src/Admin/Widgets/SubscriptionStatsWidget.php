<?php

declare(strict_types=1);

namespace DayOne\Admin\Widgets;

use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class SubscriptionStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /** @return array<Stat> */
    protected function getStats(): array
    {
        return [
            Stat::make('Active', Subscription::query()
                ->where('status', SubscriptionStatus::Active->value)
                ->count())
                ->color('success'),
            Stat::make('Trialing', Subscription::query()
                ->where('status', SubscriptionStatus::Trialing->value)
                ->count())
                ->color('info'),
            Stat::make('Canceled', Subscription::query()
                ->where('status', SubscriptionStatus::Canceled->value)
                ->count())
                ->color('danger'),
            Stat::make('Past Due', Subscription::query()
                ->where('status', SubscriptionStatus::PastDue->value)
                ->count())
                ->color('warning'),
        ];
    }
}
