<?php

declare(strict_types=1);

namespace DayOne\Admin\Resources\ProductResource\Pages;

use DayOne\Admin\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
