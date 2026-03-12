<?php

declare(strict_types=1);

namespace DayOne\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $subscription_id
 * @property string $feature
 * @property int $quantity
 * @property \Illuminate\Support\Carbon $recorded_at
 * @property string|null $stripe_usage_record_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class UsageRecord extends Model
{
    use HasUlids;

    protected $table = 'dayone_usage_records';

    protected $fillable = [
        'subscription_id',
        'feature',
        'quantity',
        'recorded_at',
        'stripe_usage_record_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
