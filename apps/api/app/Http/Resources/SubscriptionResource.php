<?php

declare(strict_types=1);

namespace App\Http\Resources;

use DayOne\DTOs\SubscriptionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SubscriptionResource extends JsonResource
{
    public function __construct(
        $resource,
        private readonly SubscriptionStatus $status,
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'product_id' => $this->product_id,
            'stripe_subscription_id' => $this->stripe_subscription_id,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'current_period_start' => $this->current_period_start?->toIso8601String(),
            'current_period_end' => $this->current_period_end?->toIso8601String(),
            'canceled_at' => $this->canceled_at?->toIso8601String(),
            'paused_at' => $this->paused_at?->toIso8601String(),
        ];
    }
}
