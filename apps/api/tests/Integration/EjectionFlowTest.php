<?php

declare(strict_types=1);

use DayOne\Contracts\Auth\V1\AuthManager;
use DayOne\Contracts\Billing\V1\BillingManager;
use DayOne\DTOs\SubscriptionStatus;
use DayOne\Ejection\EjectionManager;
use DayOne\Events\ConcernAdopted;
use DayOne\Events\ConcernEjected;
use DayOne\Exceptions\ConcernEjectedException;
use DayOne\Runtime\ProductContextInstance;
use Illuminate\Support\Facades\Event;
use Tests\Traits\InteractsWithDayOne;

uses(InteractsWithDayOne::class);

it('ejects a concern and dispatches event', function () {
    Event::fake([ConcernEjected::class]);
    $product = $this->seedProduct('Acme', 'acme');

    $manager = app(EjectionManager::class);
    $manager->eject($product, 'auth', 'Custom auth system');

    expect($manager->isEjected($product, 'auth'))->toBeTrue();
    Event::assertDispatched(ConcernEjected::class);

    $this->assertDatabaseHas('dayone_ejections', [
        'product_id' => $product->id,
        'concern' => 'auth',
    ]);
});

it('adopts a concern and dispatches event', function () {
    Event::fake([ConcernAdopted::class]);
    $product = $this->seedProduct('Acme', 'acme');

    $manager = app(EjectionManager::class);
    $manager->eject($product, 'billing');
    $manager->adopt($product, 'billing');

    expect($manager->isEjected($product, 'billing'))->toBeFalse();
    Event::assertDispatched(ConcernAdopted::class);
});

it('throws when calling ejected auth adapter methods', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $manager = app(EjectionManager::class);
    $manager->eject($product, 'auth');

    $context = app(ProductContextInstance::class);
    $context->setProduct($product);

    $auth = app(AuthManager::class);
    $auth->hasProductAccess($user, $product);
})->throws(ConcernEjectedException::class);

it('throws when calling ejected billing adapter methods', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();
    $sub = $this->seedSubscription($user, $product, SubscriptionStatus::Active);

    $manager = app(EjectionManager::class);
    $manager->eject($product, 'billing');

    $context = app(ProductContextInstance::class);
    $context->setProduct($product);

    $billing = app(BillingManager::class);
    $billing->cancelSubscription($sub);
})->throws(ConcernEjectedException::class);

it('allows adapter methods when concern is not ejected', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $context = app(ProductContextInstance::class);
    $context->setProduct($product);

    $auth = app(AuthManager::class);
    $hasAccess = $auth->hasProductAccess($user, $product);

    expect($hasAccess)->toBeFalse();
});

it('isolates ejections between products', function () {
    $acme = $this->seedProduct('Acme', 'acme');
    $beta = $this->seedProduct('Beta', 'beta');

    $manager = app(EjectionManager::class);
    $manager->eject($acme, 'auth');

    expect($manager->isEjected($acme, 'auth'))->toBeTrue();
    expect($manager->isEjected($beta, 'auth'))->toBeFalse();
});

it('allows methods after re-adopting ejected concern', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser();

    $manager = app(EjectionManager::class);
    $manager->eject($product, 'auth');
    $manager->adopt($product, 'auth');

    $context = app(ProductContextInstance::class);
    $context->setProduct($product);

    $auth = app(AuthManager::class);
    $hasAccess = $auth->hasProductAccess($user, $product);

    expect($hasAccess)->toBeFalse();
});

it('lists all ejections for a product', function () {
    $product = $this->seedProduct('Acme', 'acme');

    $manager = app(EjectionManager::class);
    $manager->eject($product, 'auth');
    $manager->eject($product, 'billing');

    $ejections = $manager->getEjections($product);
    expect($ejections)->toContain('auth', 'billing');
    expect($ejections)->not->toContain('admin');
});
