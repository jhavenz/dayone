<?php

declare(strict_types=1);

use DayOne\DTOs\SubscriptionStatus;
use DayOne\Events\PaymentFailed;
use DayOne\Events\PaymentSucceeded;
use DayOne\Events\RefundIssued;
use DayOne\Events\SubscriptionCanceled;
use DayOne\Events\SubscriptionCreated;
use DayOne\Events\SubscriptionPaused;
use DayOne\Events\SubscriptionResumed;
use DayOne\Events\SubscriptionUpdated;
use DayOne\Models\Subscription;
use Illuminate\Support\Facades\Event;
use Tests\Traits\InteractsWithDayOne;

uses(InteractsWithDayOne::class);

it('rejects webhook with invalid signature', function () {
    $response = $this->postJson('/webhooks/stripe', [
        'id' => 'evt_123',
        'type' => 'customer.subscription.created',
    ], ['Stripe-Signature' => 'invalid']);

    $response->assertStatus(403);
});

it('rejects webhook with missing signature', function () {
    $response = $this->postJson('/webhooks/stripe', [
        'id' => 'evt_123',
        'type' => 'customer.subscription.created',
    ]);

    $response->assertStatus(403);
});

it('processes subscription.created webhook', function () {
    Event::fake([SubscriptionCreated::class]);
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();
    $sub = $this->seedSubscription($user, $product, SubscriptionStatus::Incomplete);

    $webhook = $this->fakeStripeWebhook('customer.subscription.created', [
        'id' => $sub->stripe_subscription_id,
        'status' => 'active',
    ]);

    $response = $this->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $webhook['headers']['Stripe-Signature'],
        'CONTENT_TYPE' => 'application/json',
    ], $webhook['payload']);

    $response->assertOk();
    expect($response->json('status'))->toBe('processed');
    Event::assertDispatched(SubscriptionCreated::class);
});

it('deduplicates webhook events', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();
    $sub = $this->seedSubscription($user, $product, SubscriptionStatus::Active);

    $webhook = $this->fakeStripeWebhook('customer.subscription.updated', [
        'id' => $sub->stripe_subscription_id,
        'status' => 'past_due',
    ]);

    // First call
    $this->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $webhook['headers']['Stripe-Signature'],
        'CONTENT_TYPE' => 'application/json',
    ], $webhook['payload']);

    // Second call with same payload (same event ID)
    $response = $this->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $webhook['headers']['Stripe-Signature'],
        'CONTENT_TYPE' => 'application/json',
    ], $webhook['payload']);

    expect($response->json('status'))->toBe('duplicate');
});

it('processes payment succeeded webhook', function () {
    Event::fake([PaymentSucceeded::class]);

    $webhook = $this->fakeStripeWebhook('invoice.payment_succeeded', [
        'id' => 'in_test_123',
        'amount_paid' => 2999,
        'currency' => 'usd',
    ]);

    $response = $this->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $webhook['headers']['Stripe-Signature'],
        'CONTENT_TYPE' => 'application/json',
    ], $webhook['payload']);

    $response->assertOk();
    Event::assertDispatched(PaymentSucceeded::class, function ($event) {
        return $event->amountInCents === 2999;
    });
});

it('processes payment failed webhook', function () {
    Event::fake([PaymentFailed::class]);

    $webhook = $this->fakeStripeWebhook('invoice.payment_failed', [
        'id' => 'in_test_fail',
        'last_payment_error' => ['message' => 'Card declined'],
    ]);

    $response = $this->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $webhook['headers']['Stripe-Signature'],
        'CONTENT_TYPE' => 'application/json',
    ], $webhook['payload']);

    $response->assertOk();
    Event::assertDispatched(PaymentFailed::class, function ($event) {
        return $event->reason === 'Card declined';
    });
});

it('processes refund webhook', function () {
    Event::fake([RefundIssued::class]);

    $webhook = $this->fakeStripeWebhook('charge.refunded', [
        'id' => 'ch_test_refund',
        'amount_refunded' => 1500,
        'currency' => 'usd',
    ]);

    $response = $this->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $webhook['headers']['Stripe-Signature'],
        'CONTENT_TYPE' => 'application/json',
    ], $webhook['payload']);

    $response->assertOk();
    Event::assertDispatched(RefundIssued::class, function ($event) {
        return $event->amountInCents === 1500;
    });
});

it('stores webhook event in database', function () {
    $webhook = $this->fakeStripeWebhook('invoice.payment_succeeded', [
        'id' => 'in_store_test',
        'amount_paid' => 100,
        'currency' => 'usd',
    ]);

    $this->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $webhook['headers']['Stripe-Signature'],
        'CONTENT_TYPE' => 'application/json',
    ], $webhook['payload']);

    $this->assertDatabaseHas('dayone_webhook_events', [
        'type' => 'invoice.payment_succeeded',
    ]);
});
