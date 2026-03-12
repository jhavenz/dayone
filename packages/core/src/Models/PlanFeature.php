<?php

declare(strict_types=1);

namespace DayOne\Models;

use DayOne\Concerns\BelongsToProduct;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $product_id
 * @property string $plan_id
 * @property string $plan_name
 * @property string $feature_key
 * @property string|null $feature_value
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class PlanFeature extends Model
{
    use BelongsToProduct;
    use HasUlids;

    protected $table = 'dayone_plan_features';

    protected $fillable = [
        'product_id',
        'plan_id',
        'plan_name',
        'feature_key',
        'feature_value',
        'sort_order',
    ];
}
