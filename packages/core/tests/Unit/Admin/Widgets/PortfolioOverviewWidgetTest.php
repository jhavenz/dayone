<?php

declare(strict_types=1);

use DayOne\Admin\Widgets\PortfolioOverviewWidget;
use Filament\Widgets\StatsOverviewWidget;

it('extends the correct base widget class', function (): void {
    expect(is_subclass_of(PortfolioOverviewWidget::class, StatsOverviewWidget::class))->toBeTrue();
});
