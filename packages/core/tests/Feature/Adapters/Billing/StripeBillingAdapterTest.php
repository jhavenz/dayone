<?php

declare(strict_types=1);

use DayOne\Adapters\Billing\StripeBillingAdapter;
use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\DTOs\SubscriptionStatus;
use DayOne\DTOs\UsageRecord as UsageRecordDTO;
use DayOne\Events\SubscriptionCanceled;
use DayOne\Events\SubscriptionPaused;
use DayOne\Events\SubscriptionResumed;
use DayOne\Models\PlanFeature;
use DayOne\Models\Product;
use DayOne\Models\Subscription;
use DayOne\Models\UsageRecord;
use DayOne\Runtime\ProductContextInstance;
use DayOne\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->product = Product::create([
        'name' => 'Test App',
        'slug' => 'test-app',
        'is_active' => true,
    ]);

    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $context = app(ProductContextInstance::class);
    $context->setProduct($this->product);
});

it('returns correct subscription status from database record', function (): void {
    $subscription = Subscription::withoutGlobalScopes()->create([
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $adapter = app(StripeBillingAdapter::class);
    $status = $adapter->getSubscriptionStatus($subscription);

    expect($status)->toBe(SubscriptionStatus::Active);
});

it('cancels a subscription and dispatches event', function (): void {
    Event::fake([SubscriptionCanceled::class]);

    $subscription = Subscription::withoutGlobalScopes()->create([
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $adapter = app(StripeBillingAdapter::class);
    $adapter->cancelSubscription($subscription);

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Canceled);
    expect($subscription->canceled_at)->not->toBeNull();

    Event::assertDispatched(SubscriptionCanceled::class, function (SubscriptionCanceled $event) use ($subscription): bool {
        return $event->subscriptionId === $subscription->id;
    });
});

it('resumes a subscription and dispatches event', function (): void {
    Event::fake([SubscriptionResumed::class]);

    $subscription = Subscription::withoutGlobalScopes()->create([
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'status' => SubscriptionStatus::Paused,
        'paused_at' => now(),
    ]);

    $adapter = app(StripeBillingAdapter::class);
    $adapter->resumeSubscription($subscription);

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Active);
    expect($subscription->paused_at)->toBeNull();

    Event::assertDispatched(SubscriptionResumed::class);
});

it('pauses a subscription and dispatches event', function (): void {
    Event::fake([SubscriptionPaused::class]);

    $subscription = Subscription::withoutGlobalScopes()->create([
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $adapter = app(StripeBillingAdapter::class);
    $adapter->pauseSubscription($subscription);

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Paused);
    expect($subscription->paused_at)->not->toBeNull();

    Event::assertDispatched(SubscriptionPaused::class);
});

it('reports usage and returns DTO', function (): void {
    $subscription = Subscription::withoutGlobalScopes()->create([
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $adapter = app(StripeBillingAdapter::class);
    $result = $adapter->reportUsage(
        subscriptionId: $subscription->id,
        feature: 'api_calls',
        quantity: 50,
    );

    expect($result)->toBeInstanceOf(UsageRecordDTO::class);
    expect($result->feature)->toBe('api_calls');
    expect($result->quantity)->toBe(50);

    $record = UsageRecord::where('subscription_id', $subscription->id)->first();
    expect($record)->not->toBeNull();
    expect($record->feature)->toBe('api_calls');
    expect($record->quantity)->toBe(50);
});

it('syncs plans from config to plan_features table', function (): void {
    config()->set("dayone.products.test-app.plans", [
        'price_basic' => [
            'name' => 'Basic',
            'features' => [
                'api_calls' => '1000',
                'storage' => '5GB',
            ],
        ],
        'price_pro' => [
            'name' => 'Pro',
            'features' => [
                'api_calls' => 'unlimited',
                'storage' => '100GB',
                'support' => 'priority',
            ],
        ],
    ]);

    $adapter = app(StripeBillingAdapter::class);
    $adapter->syncPlans();

    $features = PlanFeature::withoutGlobalScopes()
        ->where('product_id', $this->product->id)
        ->get();

    expect($features)->toHaveCount(5);

    $basicCalls = $features->where('plan_id', 'price_basic')
        ->where('feature_key', 'api_calls')
        ->first();
    expect($basicCalls->feature_value)->toBe('1000');
    expect($basicCalls->plan_name)->toBe('Basic');
});

it('maps all stripe statuses correctly', function (): void {
    expect(StripeBillingAdapter::mapStripeStatus('active'))->toBe(SubscriptionStatus::Active);
    expect(StripeBillingAdapter::mapStripeStatus('trialing'))->toBe(SubscriptionStatus::Trialing);
    expect(StripeBillingAdapter::mapStripeStatus('past_due'))->toBe(SubscriptionStatus::PastDue);
    expect(StripeBillingAdapter::mapStripeStatus('paused'))->toBe(SubscriptionStatus::Paused);
    expect(StripeBillingAdapter::mapStripeStatus('canceled'))->toBe(SubscriptionStatus::Canceled);
    expect(StripeBillingAdapter::mapStripeStatus('incomplete'))->toBe(SubscriptionStatus::Incomplete);
    expect(StripeBillingAdapter::mapStripeStatus('incomplete_expired'))->toBe(SubscriptionStatus::IncompleteExpired);
    expect(StripeBillingAdapter::mapStripeStatus('unknown'))->toBe(SubscriptionStatus::Expired);
});
