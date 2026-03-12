<?php

declare(strict_types=1);

namespace DayOne\Admin;

use DayOne\Admin\Resources\ProductResource;
use DayOne\Admin\Resources\SubscriptionResource;
use DayOne\Admin\Widgets\PortfolioOverviewWidget;
use DayOne\Admin\Widgets\RevenueWidget;
use DayOne\Admin\Widgets\SubscriptionStatsWidget;
use Filament\Contracts\Plugin;
use Filament\Panel;

final class DayOnePlugin implements Plugin
{
    public static function make(): static
    {
        /** @var static */
        return app(static::class);
    }

    public function getId(): string
    {
        return 'dayone';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                ProductResource::class,
                SubscriptionResource::class,
            ])
            ->widgets([
                PortfolioOverviewWidget::class,
                RevenueWidget::class,
                SubscriptionStatsWidget::class,
            ]);
    }

    public function boot(Panel $panel): void {}
}
