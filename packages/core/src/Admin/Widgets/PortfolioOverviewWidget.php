<?php

declare(strict_types=1);

namespace DayOne\Admin\Widgets;

use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Product;
use DayOne\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

final class PortfolioOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    /** @return array<Stat> */
    protected function getStats(): array
    {
        return [
            Stat::make('Total Products', Product::query()->where('is_active', true)->count()),
            Stat::make('Total Subscriptions', Subscription::query()
                ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
                ->count()),
            Stat::make('Total Users', DB::table('dayone_user_products')->distinct('user_id')->count('user_id')),
        ];
    }
}
