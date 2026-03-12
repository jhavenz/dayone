<?php

declare(strict_types=1);

namespace DayOne\Adapters\Billing;

use DayOne\Contracts\Billing\V1\WebhookHandler;
use DayOne\DTOs\SubscriptionStatus;
use DayOne\Events\PaymentFailed;
use DayOne\Events\PaymentSucceeded;
use DayOne\Events\RefundIssued;
use DayOne\Events\SubscriptionCanceled;
use DayOne\Events\SubscriptionCreated;
use DayOne\Events\SubscriptionPaused;
use DayOne\Events\SubscriptionResumed;
use DayOne\Events\SubscriptionTrialEnding;
use DayOne\Events\SubscriptionUpdated;
use DayOne\Models\Product;
use DayOne\Models\Subscription;
use DayOne\Models\WebhookEvent;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class StripeWebhookHandler implements WebhookHandler
{
    public function handleWebhook(Request $request): Response
    {
        if (! $this->verifySignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();
        $stripeEventId = (string) ($payload['id'] ?? '');
        $eventType = (string) ($payload['type'] ?? '');

        $existing = WebhookEvent::where('stripe_event_id', $stripeEventId)->first();
        if ($existing !== null) {
            return response()->json(['status' => 'duplicate']);
        }

        $webhookEvent = WebhookEvent::create([
            'stripe_event_id' => $stripeEventId,
            'type' => $eventType,
            'payload' => $payload,
        ]);

        $this->dispatchEvent($eventType, $payload['data']['object'] ?? []);

        $webhookEvent->update(['processed_at' => now()]);

        return response()->json(['status' => 'processed']);
    }

    public function verifySignature(Request $request): bool
    {
        /** @var string|null $secret */
        $secret = config('dayone.billing.webhook_secret');

        if ($secret === null || $secret === '') {
            return false;
        }

        $signature = $request->header('Stripe-Signature', '');
        if ($signature === '') {
            return false;
        }

        $payload = $request->getContent();

        return $this->isValidSignature($payload, $signature, $secret);
    }

    private function isValidSignature(string $payload, string $signature, string $secret): bool
    {
        $elements = explode(',', $signature);
        $timestamp = '';
        $signatures = [];

        foreach ($elements as $element) {
            $parts = explode('=', $element, 2);
            if (count($parts) !== 2) {
                continue;
            }

            if ($parts[0] === 't') {
                $timestamp = $parts[1];
            }

            if ($parts[0] === 'v1') {
                $signatures[] = $parts[1];
            }
        }

        if ($timestamp === '' || $signatures === []) {
            return false;
        }

        $signedPayload = "{$timestamp}.{$payload}";
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function dispatchEvent(string $eventType, array $data): void
    {
        match ($eventType) {
            'customer.subscription.created' => $this->handleSubscriptionCreated($data),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($data),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($data),
            'customer.subscription.trial_will_end' => $this->handleTrialEnding($data),
            'customer.subscription.paused' => $this->handleSubscriptionPaused($data),
            'customer.subscription.resumed' => $this->handleSubscriptionResumed($data),
            'invoice.payment_succeeded' => $this->handlePaymentSucceeded($data),
            'invoice.payment_failed' => $this->handlePaymentFailed($data),
            'charge.refunded' => $this->handleRefund($data),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleSubscriptionCreated(array $data): void
    {
        $stripeSubId = (string) ($data['id'] ?? '');
        $stripeStatus = (string) ($data['status'] ?? 'active');
        $status = StripeBillingAdapter::mapStripeStatus($stripeStatus);

        $subscription = Subscription::withoutGlobalScopes()
            ->where('stripe_subscription_id', $stripeSubId)
            ->first();

        if ($subscription !== null) {
            /** @var \Illuminate\Foundation\Auth\User&\Illuminate\Contracts\Auth\Authenticatable $user */
            $user = $subscription->user;
            /** @var Product $product */
            $product = $subscription->product;

            event(new SubscriptionCreated(
                user: $user,
                product: $product,
                subscriptionId: $subscription->id,
                status: $status,
            ));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleSubscriptionUpdated(array $data): void
    {
        $stripeSubId = (string) ($data['id'] ?? '');
        $newStripeStatus = (string) ($data['status'] ?? 'active');
        $newStatus = StripeBillingAdapter::mapStripeStatus($newStripeStatus);

        $subscription = Subscription::withoutGlobalScopes()
            ->where('stripe_subscription_id', $stripeSubId)
            ->first();

        if ($subscription !== null) {
            $oldStatus = $subscription->status;
            $subscription->update(['status' => $newStatus]);

            event(new SubscriptionUpdated(
                subscriptionId: $subscription->id,
                oldStatus: $oldStatus,
                newStatus: $newStatus,
            ));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleSubscriptionDeleted(array $data): void
    {
        $stripeSubId = (string) ($data['id'] ?? '');

        $subscription = Subscription::withoutGlobalScopes()
            ->where('stripe_subscription_id', $stripeSubId)
            ->first();

        $product = $subscription?->product;
        $subscriptionId = $subscription !== null ? $subscription->id : $stripeSubId;

        $subscription?->update([
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
        ]);

        event(new SubscriptionCanceled(
            subscriptionId: $subscriptionId,
            product: $product,
        ));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleTrialEnding(array $data): void
    {
        $stripeSubId = (string) ($data['id'] ?? '');
        $trialEnd = $data['trial_end'] ?? null;

        $subscription = Subscription::withoutGlobalScopes()
            ->where('stripe_subscription_id', $stripeSubId)
            ->first();

        $subscriptionId = $subscription !== null ? $subscription->id : $stripeSubId;
        $trialEndsAt = $trialEnd !== null
            ? new \DateTimeImmutable('@' . $trialEnd)
            : new \DateTimeImmutable();

        event(new SubscriptionTrialEnding(
            subscriptionId: $subscriptionId,
            trialEndsAt: $trialEndsAt,
        ));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleSubscriptionPaused(array $data): void
    {
        $stripeSubId = (string) ($data['id'] ?? '');

        $subscription = Subscription::withoutGlobalScopes()
            ->where('stripe_subscription_id', $stripeSubId)
            ->first();

        $subscriptionId = $subscription !== null ? $subscription->id : $stripeSubId;

        $subscription?->update([
            'status' => SubscriptionStatus::Paused,
            'paused_at' => now(),
        ]);

        event(new SubscriptionPaused(subscriptionId: $subscriptionId));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleSubscriptionResumed(array $data): void
    {
        $stripeSubId = (string) ($data['id'] ?? '');

        $subscription = Subscription::withoutGlobalScopes()
            ->where('stripe_subscription_id', $stripeSubId)
            ->first();

        $subscriptionId = $subscription !== null ? $subscription->id : $stripeSubId;

        $subscription?->update([
            'status' => SubscriptionStatus::Active,
            'paused_at' => null,
        ]);

        event(new SubscriptionResumed(subscriptionId: $subscriptionId));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handlePaymentSucceeded(array $data): void
    {
        event(new PaymentSucceeded(
            invoiceId: (string) ($data['id'] ?? ''),
            amountInCents: (int) ($data['amount_paid'] ?? 0),
            currency: (string) ($data['currency'] ?? 'usd'),
        ));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handlePaymentFailed(array $data): void
    {
        /** @var array{message?: string}|null $lastError */
        $lastError = $data['last_payment_error'] ?? null;

        event(new PaymentFailed(
            invoiceId: (string) ($data['id'] ?? ''),
            reason: (string) ($lastError['message'] ?? 'Payment failed'),
        ));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleRefund(array $data): void
    {
        event(new RefundIssued(
            chargeId: (string) ($data['id'] ?? ''),
            amountInCents: (int) ($data['amount_refunded'] ?? 0),
            currency: (string) ($data['currency'] ?? 'usd'),
        ));
    }
}
