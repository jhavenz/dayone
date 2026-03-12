<?php

declare(strict_types=1);

namespace DayOne\Adapters\Billing;

use DayOne\Concerns\GuardsEjection;
use DayOne\Contracts\Billing\V1\BillingManager;
use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\DTOs\CheckoutSession;
use DayOne\DTOs\CheckoutType;
use DayOne\DTOs\PlanDefinition;
use DayOne\DTOs\SubscriptionStatus;
use DayOne\DTOs\UsageRecord as UsageRecordDTO;
use DayOne\Events\SubscriptionCanceled;
use DayOne\Events\SubscriptionCreated;
use DayOne\Events\SubscriptionPaused;
use DayOne\Events\SubscriptionResumed;
use DayOne\Models\PlanFeature;
use DayOne\Models\Subscription;
use DayOne\Models\UsageRecord;
use Illuminate\Contracts\Auth\Authenticatable;

final class StripeBillingAdapter implements BillingManager
{
    use GuardsEjection;

    public function __construct(
        private readonly ProductContext $context,
    ) {}

    public function createCheckout(PlanDefinition $plan, mixed $billable, CheckoutType $type): CheckoutSession
    {
        $this->guardEjection('billing');
        $product = $this->context->requireProduct();

        $user = $billable;
        assert($user instanceof \Illuminate\Foundation\Auth\User);

        /** @phpstan-ignore method.notFound (Billable trait provides newSubscription) */
        $checkout = $user->newSubscription(
            type: $product->slug,
            prices: $plan->id,
        )->checkout([
            'success_url' => config('app.url') . '/billing/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.url') . '/billing/cancel',
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->getAuthIdentifier(),
            'product_id' => $product->id,
            'stripe_price_id' => $plan->id,
            'status' => SubscriptionStatus::Incomplete,
        ]);

        event(new SubscriptionCreated(
            user: $user,
            product: $product,
            subscriptionId: $subscription->id,
            status: SubscriptionStatus::Incomplete,
        ));

        /** @var string $checkoutId */
        $checkoutId = $checkout->id ?? '';
        /** @var string $checkoutUrl */
        $checkoutUrl = $checkout->url ?? '';

        return new CheckoutSession(
            id: $checkoutId,
            url: $checkoutUrl,
            status: 'open',
            customerId: $user->stripe_id ?? null,
        );
    }

    public function cancelSubscription(Subscription $subscription): void
    {
        $this->guardEjection('billing');

        $subscription->update([
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
        ]);

        event(new SubscriptionCanceled(
            subscriptionId: $subscription->id,
            product: $subscription->product,
        ));
    }

    public function resumeSubscription(Subscription $subscription): void
    {
        $this->guardEjection('billing');

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'canceled_at' => null,
            'paused_at' => null,
        ]);

        event(new SubscriptionResumed(subscriptionId: $subscription->id));
    }

    public function pauseSubscription(Subscription $subscription): void
    {
        $this->guardEjection('billing');

        $subscription->update([
            'status' => SubscriptionStatus::Paused,
            'paused_at' => now(),
        ]);

        event(new SubscriptionPaused(subscriptionId: $subscription->id));
    }

    public function getSubscriptionStatus(Subscription $subscription): SubscriptionStatus
    {
        $this->guardEjection('billing');

        return $subscription->status;
    }

    public function reportUsage(string $subscriptionId, string $feature, int $quantity): UsageRecordDTO
    {
        $this->guardEjection('billing');

        $now = new \DateTimeImmutable();

        UsageRecord::create([
            'subscription_id' => $subscriptionId,
            'feature' => $feature,
            'quantity' => $quantity,
            'recorded_at' => $now,
        ]);

        return new UsageRecordDTO(
            subscriptionId: $subscriptionId,
            feature: $feature,
            quantity: $quantity,
            recordedAt: $now,
        );
    }

    public function syncPlans(): void
    {
        $this->guardEjection('billing');
        $product = $this->context->requireProduct();

        /** @var array<string, array{name: string, features: array<string, string>}> $plans */
        $plans = config("dayone.products.{$product->slug}.plans", []);

        PlanFeature::withoutGlobalScopes()
            ->where('product_id', $product->id)
            ->delete();

        $sortOrder = 0;
        foreach ($plans as $planId => $planConfig) {
            $planName = $planConfig['name'];
            $features = $planConfig['features'];

            foreach ($features as $featureKey => $featureValue) {
                PlanFeature::create([
                    'product_id' => $product->id,
                    'plan_id' => $planId,
                    'plan_name' => $planName,
                    'feature_key' => $featureKey,
                    'feature_value' => (string) $featureValue,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
    }

    public static function mapStripeStatus(string $stripeStatus): SubscriptionStatus
    {
        return match ($stripeStatus) {
            'active' => SubscriptionStatus::Active,
            'trialing' => SubscriptionStatus::Trialing,
            'past_due' => SubscriptionStatus::PastDue,
            'paused' => SubscriptionStatus::Paused,
            'canceled' => SubscriptionStatus::Canceled,
            'incomplete' => SubscriptionStatus::Incomplete,
            'incomplete_expired' => SubscriptionStatus::IncompleteExpired,
            default => SubscriptionStatus::Expired,
        };
    }
}
