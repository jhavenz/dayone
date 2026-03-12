<?php

declare(strict_types=1);

namespace DayOne\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $stripe_event_id
 * @property string $type
 * @property array<string, mixed> $payload
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class WebhookEvent extends Model
{
    use HasUlids;

    protected $table = 'dayone_webhook_events';

    protected $fillable = [
        'stripe_event_id',
        'type',
        'payload',
        'processed_at',
    ];

    public bool $isProcessed {
        get => $this->processed_at !== null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
