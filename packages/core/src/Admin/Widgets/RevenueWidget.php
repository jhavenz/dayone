<?php

declare(strict_types=1);

namespace DayOne\Admin\Widgets;

use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class RevenueWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Revenue Indicators';

    /** @return array<Stat> */
    protected function getStats(): array
    {
        $byStatus = Subscription::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            Stat::make('Paying Subscriptions', (int) ($byStatus[SubscriptionStatus::Active->value] ?? 0))
                ->description('Active subscriptions generating revenue'),
            Stat::make('Trial Conversions Pending', (int) ($byStatus[SubscriptionStatus::Trialing->value] ?? 0))
                ->description('Trialing subscriptions awaiting conversion'),
            Stat::make('At Risk', (int) ($byStatus[SubscriptionStatus::PastDue->value] ?? 0))
                ->description('Past due subscriptions requiring attention')
                ->color('danger'),
        ];
    }
}
