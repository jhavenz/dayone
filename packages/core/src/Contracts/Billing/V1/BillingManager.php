<?php

declare(strict_types=1);

namespace DayOne\Contracts\Billing\V1;

use DayOne\DTOs\CheckoutSession;
use DayOne\DTOs\CheckoutType;
use DayOne\DTOs\PlanDefinition;
use DayOne\DTOs\SubscriptionStatus;
use DayOne\DTOs\UsageRecord;
use DayOne\Models\Subscription;

interface BillingManager
{
    public function createCheckout(PlanDefinition $plan, mixed $billable, CheckoutType $type): CheckoutSession;

    public function cancelSubscription(Subscription $subscription): void;

    public function resumeSubscription(Subscription $subscription): void;

    public function pauseSubscription(Subscription $subscription): void;

    public function getSubscriptionStatus(Subscription $subscription): SubscriptionStatus;

    public function reportUsage(string $subscriptionId, string $feature, int $quantity): UsageRecord;

    public function syncPlans(): void;
}
