<?php

declare(strict_types=1);

namespace DayOne\Admin\Resources\ProductResource\Pages;

use DayOne\Admin\Resources\ProductResource;
use Filament\Resources\Pages\EditRecord;

final class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
}
