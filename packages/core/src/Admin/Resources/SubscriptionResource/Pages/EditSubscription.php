<?php

declare(strict_types=1);

namespace DayOne\Admin\Resources\SubscriptionResource\Pages;

use DayOne\Admin\Resources\SubscriptionResource;
use Filament\Resources\Pages\EditRecord;

final class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;
}
