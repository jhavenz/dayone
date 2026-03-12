<?php

declare(strict_types=1);

namespace DayOne\Admin;

use DayOne\Admin\Resources\ProductResource;
use DayOne\Admin\Resources\SubscriptionResource;
use DayOne\Admin\Widgets\PortfolioOverviewWidget;
use DayOne\Admin\Widgets\RevenueWidget;
use DayOne\Admin\Widgets\SubscriptionStatsWidget;
use DayOne\Models\Product;
use Filament\Panel;
use Filament\PanelProvider;

class DayOneAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        /** @var string $path */
        $path = config('dayone.admin.path', 'dayone-admin');

        /** @var string $guard */
        $guard = config('dayone.admin.auth_guard', 'web');

        return $panel
            ->id('dayone-admin')
            ->path($path)
            ->authGuard($guard)
            ->colors([
                'primary' => '#6366f1',
            ])
            ->tenant(Product::class)
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
}
