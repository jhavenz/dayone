<?php

declare(strict_types=1);

use App\Models\User;
use DayOne\Events\TokenIssued;
use DayOne\Events\TokenRevoked;
use Illuminate\Support\Facades\Event;
use Tests\Traits\InteractsWithDayOne;

uses(InteractsWithDayOne::class);

it('registers a new user and returns token', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@test.com',
        'password' => 'password123',
    ]);

    $response->assertCreated();
    expect($response->json('token'))->toBeString()->not->toBeEmpty();
    expect($response->json('user.email'))->toBe('jane@test.com');

    $this->assertDatabaseHas('users', ['email' => 'jane@test.com']);
});

it('dispatches TokenIssued event on register', function () {
    Event::fake([TokenIssued::class]);

    $this->postJson('/api/auth/register', [
        'name' => 'Jane',
        'email' => 'jane@test.com',
        'password' => 'password123',
    ]);

    Event::assertDispatched(TokenIssued::class);
});

it('rejects registration with duplicate email', function () {
    User::factory()->create(['email' => 'taken@test.com']);

    $response = $this->postJson('/api/auth/register', [
        'name' => 'Dupe',
        'email' => 'taken@test.com',
        'password' => 'password123',
    ]);

    $response->assertUnprocessable();
});

it('logs in with valid credentials', function () {
    User::factory()->create([
        'email' => 'login@test.com',
        'password' => 'secret123',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'login@test.com',
        'password' => 'secret123',
    ]);

    $response->assertOk();
    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

it('rejects login with wrong password', function () {
    User::factory()->create([
        'email' => 'login@test.com',
        'password' => 'secret123',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'login@test.com',
        'password' => 'wrongpass',
    ]);

    $response->assertUnprocessable();
});

it('rejects login with nonexistent email', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'nobody@test.com',
        'password' => 'password123',
    ]);

    $response->assertUnprocessable();
});

it('logs out and revokes token', function () {
    Event::fake([TokenRevoked::class]);
    [$user, $token] = $this->authenticatedUser();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/auth/logout');

    $response->assertOk();
    Event::assertDispatched(TokenRevoked::class);
});

it('rejects logout without token', function () {
    $response = $this->postJson('/api/auth/logout');

    $response->assertUnauthorized();
});

it('rejects protected routes without token', function () {
    $this->seedProduct('Acme', 'acme');

    $response = $this->getJson('/api/acme/access/check');

    $response->assertUnauthorized();
});

it('issues product-scoped token when context is set', function () {
    $product = $this->seedProduct('Acme', 'acme');
    [$user, $token] = $this->authenticatedUser('acme');

    $personalToken = $user->tokens()->first();
    $abilities = $personalToken->abilities;

    expect($abilities)->toContain('product:acme');
});
