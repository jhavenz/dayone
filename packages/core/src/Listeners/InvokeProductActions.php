<?php

declare(strict_types=1);

namespace DayOne\Listeners;

use DayOne\Events\PaymentFailed;
use DayOne\Events\PaymentSucceeded;
use DayOne\Events\RefundIssued;
use DayOne\Events\SubscriptionCanceled;
use DayOne\Events\SubscriptionCreated;
use DayOne\Events\SubscriptionExpired;
use DayOne\Events\SubscriptionPaused;
use DayOne\Events\SubscriptionResumed;
use DayOne\Events\SubscriptionTrialEnding;
use DayOne\Events\SubscriptionUpdated;
use DayOne\Models\Product;
use DayOne\Models\Subscription;

final class InvokeProductActions
{
    /**
     * @param array<class-string, string> $eventActionMap
     */
    private const array EVENT_ACTION_MAP = [
        SubscriptionCreated::class => 'on_subscribe',
        SubscriptionCanceled::class => 'on_cancel',
        SubscriptionExpired::class => 'on_expire',
        SubscriptionResumed::class => 'on_resume',
        SubscriptionPaused::class => 'on_pause',
        SubscriptionUpdated::class => 'on_update',
        SubscriptionTrialEnding::class => 'on_trial_ending',
        PaymentSucceeded::class => 'on_payment_succeeded',
        PaymentFailed::class => 'on_payment_failed',
        RefundIssued::class => 'on_refund',
    ];

    public function handle(object $event): void
    {
        $actionKey = self::EVENT_ACTION_MAP[$event::class] ?? null;

        if ($actionKey === null) {
            return;
        }

        $product = $this->resolveProduct($event);

        if ($product === null) {
            return;
        }

        /** @var mixed $action */
        $action = config("dayone.products.{$product->slug}.actions.{$actionKey}");

        if ($action === null) {
            return;
        }

        if ($action instanceof \Closure) {
            $action($event);
            return;
        }

        if (is_string($action) && class_exists($action)) {
            app($action)->handle($event);
        }
    }

    private function resolveProduct(object $event): ?Product
    {
        if (property_exists($event, 'product') && $event->product instanceof Product) {
            return $event->product;
        }

        if (! property_exists($event, 'subscriptionId')) {
            return null;
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::withoutGlobalScopes()
            ->with('product')
            ->find($event->subscriptionId);

        return $subscription?->product;
    }
}
