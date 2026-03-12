<?php

declare(strict_types=1);

use DayOne\Events\ProductAccessGranted;
use DayOne\Events\ProductAccessRevoked;
use DayOne\Events\SubscriptionCanceled;
use DayOne\Events\TokenIssued;
use DayOne\DTOs\SubscriptionStatus;
use Illuminate\Support\Facades\Event;
use Tests\Traits\InteractsWithDayOne;

uses(InteractsWithDayOne::class);

it('dispatches ProductAccessGranted on grant', function () {
    Event::fake([ProductAccessGranted::class]);
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant');

    Event::assertDispatched(ProductAccessGranted::class, function ($event) use ($user, $product) {
        return $event->user->getAuthIdentifier() === $user->id
            && $event->product->id === $product->id;
    });
});

it('dispatches ProductAccessRevoked on revoke', function () {
    Event::fake([ProductAccessRevoked::class]);
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant');
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/revoke');

    Event::assertDispatched(ProductAccessRevoked::class);
});

it('dispatches TokenIssued on registration', function () {
    Event::fake([TokenIssued::class]);

    $this->postJson('/api/auth/register', [
        'name' => 'Event User',
        'email' => 'event@test.com',
        'password' => 'password123',
    ]);

    Event::assertDispatched(TokenIssued::class, function ($event) {
        return $event->user->email === 'event@test.com';
    });
});

it('dispatches SubscriptionCanceled on cancel', function () {
    Event::fake([SubscriptionCanceled::class]);
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();
    $sub = $this->seedSubscription($user, $product, SubscriptionStatus::Active);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/subscription/cancel', [
            'subscription_id' => $sub->id,
        ]);

    Event::assertDispatched(SubscriptionCanceled::class, function ($event) use ($sub) {
        return $event->subscriptionId === $sub->id;
    });
});

it('dispatches events with correct product context', function () {
    Event::fake([ProductAccessGranted::class]);
    $acme = $this->seedProduct('Acme', 'acme');
    $beta = $this->seedProduct('Beta', 'beta');
    [$user, $token] = $this->authenticatedUser();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/acme/access/grant');
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/beta/access/grant');

    Event::assertDispatched(ProductAccessGranted::class, function ($event) use ($acme) {
        return $event->product->id === $acme->id;
    });
    Event::assertDispatched(ProductAccessGranted::class, function ($event) use ($beta) {
        return $event->product->id === $beta->id;
    });
});

it('TokenIssued event includes token ID', function () {
    Event::fake([TokenIssued::class]);

    $this->postJson('/api/auth/register', [
        'name' => 'Token Event',
        'email' => 'tokenevent@test.com',
        'password' => 'password123',
    ]);

    Event::assertDispatched(TokenIssued::class, function ($event) {
        return $event->tokenId !== '' && $event->tokenId !== null;
    });
});
