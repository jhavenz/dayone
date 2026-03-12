<?php

declare(strict_types=1);

namespace DayOne\Models;

use DayOne\Concerns\BelongsToProduct;
use DayOne\DTOs\SubscriptionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property int $user_id
 * @property string $product_id
 * @property string|null $stripe_subscription_id
 * @property string|null $stripe_price_id
 * @property SubscriptionStatus $status
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property \Illuminate\Support\Carbon|null $current_period_start
 * @property \Illuminate\Support\Carbon|null $current_period_end
 * @property \Illuminate\Support\Carbon|null $canceled_at
 * @property \Illuminate\Support\Carbon|null $paused_at
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class Subscription extends Model
{
    use BelongsToProduct;
    use HasUlids;

    protected $table = 'dayone_subscriptions';

    protected $fillable = [
        'user_id',
        'product_id',
        'stripe_subscription_id',
        'stripe_price_id',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'canceled_at',
        'paused_at',
        'metadata',
    ];

    public bool $isUsable {
        get => $this->status->isUsable();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'metadata' => 'array',
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'canceled_at' => 'datetime',
            'paused_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<\Illuminate\Foundation\Auth\User, $this> */
    public function user(): BelongsTo
    {
        /** @var class-string<\Illuminate\Foundation\Auth\User> $userModel */
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');

        return $this->belongsTo($userModel);
    }

    /** @return HasMany<UsageRecord, $this> */
    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }
}
