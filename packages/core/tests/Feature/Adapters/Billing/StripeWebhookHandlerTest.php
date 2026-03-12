<?php

declare(strict_types=1);

use DayOne\Adapters\Billing\StripeWebhookHandler;
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
use DayOne\Tests\Fixtures\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

function makeSignedRequest(string $eventId, string $type, array $data = [], ?string $secret = null): Request
{
    $secret ??= 'whsec_test_secret';
    config()->set('dayone.billing.webhook_secret', $secret);

    $payload = json_encode([
        'id' => $eventId,
        'type' => $type,
        'data' => ['object' => $data],
    ]);

    $timestamp = (string) time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    $request = Request::create(
        uri: '/webhooks/stripe',
        method: 'POST',
        content: $payload,
    );
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('Stripe-Signature', "t={$timestamp},v1={$signature}");

    return $request;
}

it('logs webhook event to database', function (): void {
    Event::fake();

    $request = makeSignedRequest('evt_123', 'invoice.payment_succeeded', [
        'id' => 'inv_123',
        'amount_paid' => 2000,
        'currency' => 'usd',
    ]);

    $handler = new StripeWebhookHandler();
    $response = $handler->handleWebhook($request);

    expect($response->getStatusCode())->toBe(200);

    $event = WebhookEvent::where('stripe_event_id', 'evt_123')->first();
    expect($event)->not->toBeNull();
    expect($event->type)->toBe('invoice.payment_succeeded');
    expect($event->isProcessed)->toBeTrue();
});

it('deduplicates webhook events by stripe_event_id', function (): void {
    Event::fake();

    $request1 = makeSignedRequest('evt_dup', 'invoice.payment_succeeded', [
        'id' => 'inv_123',
        'amount_paid' => 2000,
        'currency' => 'usd',
    ]);

    $handler = new StripeWebhookHandler();
    $handler->handleWebhook($request1);

    $request2 = makeSignedRequest('evt_dup', 'invoice.payment_succeeded', [
        'id' => 'inv_123',
        'amount_paid' => 2000,
        'currency' => 'usd',
    ]);

    $response = $handler->handleWebhook($request2);
    $body = json_decode($response->getContent(), true);

    expect($body['status'])->toBe('duplicate');
    expect(WebhookEvent::where('stripe_event_id', 'evt_dup')->count())->toBe(1);
});

it('dispatches SubscriptionCreated for customer.subscription.created', function (): void {
    Event::fake([SubscriptionCreated::class]);

    $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
    $user = User::create(['name' => 'User', 'email' => 'u@example.com', 'password' => 'pass']);

    $subscription = Subscription::withoutGlobalScopes()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'stripe_subscription_id' => 'sub_stripe_123',
        'status' => SubscriptionStatus::Incomplete,
    ]);

    $request = makeSignedRequest('evt_sub_created', 'customer.subscription.created', [
        'id' => 'sub_stripe_123',
        'status' => 'active',
    ]);

    $handler = new StripeWebhookHandler();
    $handler->handleWebhook($request);

    Event::assertDispatched(SubscriptionCreated::class);
});

it('dispatches SubscriptionUpdated and updates status', function (): void {
    Event::fake([SubscriptionUpdated::class]);

    $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
    $user = User::create(['name' => 'User', 'email' => 'u@example.com', 'password' => 'pass']);

    $subscription = Subscription::withoutGlobalScopes()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'stripe_subscription_id' => 'sub_upd_123',
        'status' => SubscriptionStatus::Active,
    ]);

    $request = makeSignedRequest('evt_sub_updated', 'customer.subscription.updated', [
        'id' => 'sub_upd_123',
        'status' => 'past_due',
    ]);

    $handler = new StripeWebhookHandler();
    $handler->handleWebhook($request);

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::PastDue);

    Event::assertDispatched(SubscriptionUpdated::class, function (SubscriptionUpdated $e) {
        return $e->oldStatus === SubscriptionStatus::Active
            && $e->newStatus === SubscriptionStatus::PastDue;
    });
});

it('dispatches SubscriptionCanceled for customer.subscription.deleted', function (): void {
    Event::fake([SubscriptionCanceled::class]);

    $product = Product::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
    $user = User::create(['name' => 'User', 'email' => 'u@example.com', 'password' => 'pass']);

    Subscription::withoutGlobalScopes()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'stripe_subscription_id' => 'sub_del_123',
        'status' => SubscriptionStatus::Active,
    ]);

    $request = makeSignedRequest('evt_sub_deleted', 'customer.subscription.deleted', [
        'id' => 'sub_del_123',
    ]);

    $handler = new StripeWebhookHandler();
    $handler->handleWebhook($request);

    Event::assertDispatched(SubscriptionCanceled::class);
});

it('dispatches PaymentSucceeded for invoice.payment_succeeded', function (): void {
    Event::fake([PaymentSucceeded::class]);

    $request = makeSignedRequest('evt_pay_ok', 'invoice.payment_succeeded', [
        'id' => 'inv_456',
        'amount_paid' => 5000,
        'currency' => 'usd',
    ]);

    $handler = new StripeWebhookHandler();
    $handler->handleWebhook($request);

    Event::assertDispatched(PaymentSucceeded::class, function (PaymentSucceeded $e): bool {
        return $e->invoiceId === 'inv_456'
            && $e->amountInCents === 5000
            && $e->currency === 'usd';
    });
});

it('dispatches PaymentFailed for invoice.payment_failed', function (): void {
    Event::fake([PaymentFailed::class]);

    $request = makeSignedRequest('evt_pay_fail', 'invoice.payment_failed', [
        'id' => 'inv_789',
        'last_payment_error' => ['message' => 'Card declined'],
    ]);

    $handler = new StripeWebhookHandler();
    $handler->handleWebhook($request);

    Event::assertDispatched(PaymentFailed::class, function (PaymentFailed $e): bool {
        return $e->invoiceId === 'inv_789' && $e->reason === 'Card declined';
    });
});

it('dispatches RefundIssued for charge.refunded', function (): void {
    Event::fake([RefundIssued::class]);

    $request = makeSignedRequest('evt_refund', 'charge.refunded', [
        'id' => 'ch_123',
        'amount_refunded' => 1500,
        'currency' => 'eur',
    ]);

    $handler = new StripeWebhookHandler();
    $handler->handleWebhook($request);

    Event::assertDispatched(RefundIssued::class, function (RefundIssued $e): bool {
        return $e->chargeId === 'ch_123'
            && $e->amountInCents === 1500
            && $e->currency === 'eur';
    });
});

it('dispatches SubscriptionTrialEnding for trial_will_end', function (): void {
    Event::fake([SubscriptionTrialEnding::class]);

    $request = makeSignedRequest('evt_trial', 'customer.subscription.trial_will_end', [
        'id' => 'sub_trial_123',
        'trial_end' => 1700000000,
    ]);

    $handler = new StripeWebhookHandler();
    $handler->handleWebhook($request);

    Event::assertDispatched(SubscriptionTrialEnding::class);
});

it('dispatches SubscriptionPaused for customer.subscription.paused', function (): void {
    Event::fake([SubscriptionPaused::class]);

    $request = makeSignedRequest('evt_pause', 'customer.subscription.paused', [
        'id' => 'sub_pause_123',
    ]);

    $handler = new StripeWebhookHandler();
    $handler->handleWebhook($request);

    Event::assertDispatched(SubscriptionPaused::class);
});

it('dispatches SubscriptionResumed for customer.subscription.resumed', function (): void {
    Event::fake([SubscriptionResumed::class]);

    $request = makeSignedRequest('evt_resume', 'customer.subscription.resumed', [
        'id' => 'sub_resume_123',
    ]);

    $handler = new StripeWebhookHandler();
    $handler->handleWebhook($request);

    Event::assertDispatched(SubscriptionResumed::class);
});

it('does not dispatch events for unknown webhook types', function (): void {
    $billingEvents = [
        SubscriptionCreated::class,
        SubscriptionUpdated::class,
        SubscriptionCanceled::class,
        SubscriptionPaused::class,
        SubscriptionResumed::class,
        SubscriptionTrialEnding::class,
        PaymentSucceeded::class,
        PaymentFailed::class,
        RefundIssued::class,
    ];

    Event::fake($billingEvents);

    $request = makeSignedRequest('evt_unknown', 'some.unknown.event', ['id' => 'obj_123']);

    $handler = new StripeWebhookHandler();
    $response = $handler->handleWebhook($request);

    expect($response->getStatusCode())->toBe(200);
    Event::assertNotDispatched(SubscriptionCreated::class);
    Event::assertNotDispatched(SubscriptionUpdated::class);
    Event::assertNotDispatched(SubscriptionCanceled::class);
    Event::assertNotDispatched(PaymentSucceeded::class);
    Event::assertNotDispatched(PaymentFailed::class);
    Event::assertNotDispatched(RefundIssued::class);
});

it('rejects webhook with invalid signature', function (): void {
    config()->set('dayone.billing.webhook_secret', 'whsec_real_secret');

    $payload = json_encode(['id' => 'evt_bad', 'type' => 'test']);
    $request = Request::create('/webhooks/stripe', 'POST', content: $payload);
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('Stripe-Signature', 't=123,v1=invalidsig');

    $handler = new StripeWebhookHandler();
    $response = $handler->handleWebhook($request);

    expect($response->getStatusCode())->toBe(403);
});
