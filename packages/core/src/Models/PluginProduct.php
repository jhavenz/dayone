<?php

declare(strict_types=1);

namespace DayOne\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class PluginProduct extends Pivot
{
    use HasUlids;

    protected $table = 'dayone_plugin_products';

    public $incrementing = false;

    /** @var string */
    protected $keyType = 'string';
}
