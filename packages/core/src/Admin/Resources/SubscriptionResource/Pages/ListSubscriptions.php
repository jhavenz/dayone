<?php

declare(strict_types=1);

namespace DayOne\Admin\Resources\SubscriptionResource\Pages;

use DayOne\Admin\Resources\SubscriptionResource;
use Filament\Resources\Pages\ListRecords;

final class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;
}
