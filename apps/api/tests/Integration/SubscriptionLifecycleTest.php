<?php

declare(strict_types=1);

use DayOne\DTOs\SubscriptionStatus;
use DayOne\Events\SubscriptionCanceled;
use DayOne\Events\SubscriptionPaused;
use DayOne\Events\SubscriptionResumed;
use DayOne\Models\Subscription;
use Illuminate\Support\Facades\Event;
use Tests\Traits\InteractsWithDayOne;

uses(InteractsWithDayOne::class);

it('cancels a subscription', function () {
    Event::fake([SubscriptionCanceled::class]);
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();
    $sub = $this->seedSubscription($user, $product, SubscriptionStatus::Active);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/subscription/cancel', [
            'subscription_id' => $sub->id,
        ]);

    $response->assertOk();
    Event::assertDispatched(SubscriptionCanceled::class);

    $sub->refresh();
    expect($sub->status)->toBe(SubscriptionStatus::Canceled);
    expect($sub->canceled_at)->not->toBeNull();
});

it('resumes a canceled subscription', function () {
    Event::fake([SubscriptionResumed::class]);
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();
    $sub = $this->seedSubscription($user, $product, SubscriptionStatus::Canceled);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/subscription/resume', [
            'subscription_id' => $sub->id,
        ]);

    $response->assertOk();
    Event::assertDispatched(SubscriptionResumed::class);

    $sub->refresh();
    expect($sub->status)->toBe(SubscriptionStatus::Active);
});

it('pauses a subscription', function () {
    Event::fake([SubscriptionPaused::class]);
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();
    $sub = $this->seedSubscription($user, $product, SubscriptionStatus::Active);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/subscription/pause', [
            'subscription_id' => $sub->id,
        ]);

    $response->assertOk();
    Event::assertDispatched(SubscriptionPaused::class);

    $sub->refresh();
    expect($sub->status)->toBe(SubscriptionStatus::Paused);
    expect($sub->paused_at)->not->toBeNull();
});

it('gets subscription status', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();
    $sub = $this->seedSubscription($user, $product, SubscriptionStatus::Active);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/acme/subscription?subscription_id=' . $sub->id);

    $response->assertOk();
    expect($response->json('status'))->toBe('active');
});

it('returns correct status for trialing subscription', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();
    $sub = $this->seedSubscription($user, $product, SubscriptionStatus::Trialing);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/acme/subscription?subscription_id=' . $sub->id);

    expect($response->json('status'))->toBe('trialing');
});

it('scopes subscription operations to product context', function () {
    $acme = $this->seedProduct('Acme', 'acme');
    $beta = $this->seedProduct('Beta', 'beta');
    [$user, $token] = $this->authenticatedUser();

    $acmeSub = $this->seedSubscription($user, $acme, SubscriptionStatus::Active);
    $betaSub = $this->seedSubscription($user, $beta, SubscriptionStatus::Active);

    // Cancel acme subscription via acme route
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/subscription/cancel', [
            'subscription_id' => $acmeSub->id,
        ]);

    $acmeSub->refresh();
    $betaSub->refresh();

    expect($acmeSub->status)->toBe(SubscriptionStatus::Canceled);
    expect($betaSub->status)->toBe(SubscriptionStatus::Active);
});

it('returns subscription status DTO values correctly', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    foreach (['active', 'trialing', 'past_due', 'paused', 'canceled'] as $statusValue) {
        $status = SubscriptionStatus::from($statusValue);
        $sub = $this->seedSubscription($user, $product, $status);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/acme/subscription?subscription_id=' . $sub->id);

        expect($response->json('status'))->toBe($statusValue);
    }
});

it('validates subscription_id is required for cancel', function () {
    $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/subscription/cancel');

    $response->assertUnprocessable();
});

it('handles pause on already paused subscription', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();
    $sub = $this->seedSubscription($user, $product, SubscriptionStatus::Paused);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/subscription/pause', [
            'subscription_id' => $sub->id,
        ]);

    $response->assertOk();
    $sub->refresh();
    expect($sub->status)->toBe(SubscriptionStatus::Paused);
});

it('resumes a paused subscription', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();
    $sub = $this->seedSubscription($user, $product, SubscriptionStatus::Paused);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/subscription/resume', [
            'subscription_id' => $sub->id,
        ]);

    $response->assertOk();
    $sub->refresh();
    expect($sub->status)->toBe(SubscriptionStatus::Active);
    expect($sub->paused_at)->toBeNull();
});
