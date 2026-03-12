<?php

declare(strict_types=1);

use DayOne\Support\ConfigValidator;

beforeEach(function (): void {
    $this->validator = new ConfigValidator();
});

it('returns no errors for valid config', function (): void {
    config()->set('dayone.billing.webhook_secret', 'whsec_test_secret');

    $errors = $this->validator->validate();

    expect($errors)->toBe([]);
});

it('returns error when billing provider is missing', function (): void {
    config()->set('dayone.billing.provider', null);

    $errors = $this->validator->validate();

    expect($errors)->toContain('billing.provider is not set.');
});

it('returns error when billing provider is invalid', function (): void {
    config()->set('dayone.billing.provider', 'paypal');
    config()->set('dayone.billing.webhook_secret', 'whsec_test');

    $errors = $this->validator->validate();

    $found = false;
    foreach ($errors as $error) {
        if (str_contains($error, "'paypal' is not valid")) {
            $found = true;
        }
    }

    expect($found)->toBeTrue();
});

it('returns error when resolver strategies is empty', function (): void {
    config()->set('dayone.resolver.strategies', []);

    $errors = $this->validator->validate();

    expect($errors)->toContain('resolver.strategies must be a non-empty array.');
});

it('returns error when admin path is missing', function (): void {
    config()->set('dayone.admin.path', null);

    $errors = $this->validator->validate();

    expect($errors)->toContain('admin.path is not set.');
});

it('returns error when ejection concerns contains invalid value', function (): void {
    config()->set('dayone.ejection.concerns', ['billing', 'invalid']);

    $errors = $this->validator->validate();

    $found = false;
    foreach ($errors as $error) {
        if (str_contains($error, "'invalid'")) {
            $found = true;
        }
    }

    expect($found)->toBeTrue();
});

it('returns multiple errors for multiple invalid items', function (): void {
    config()->set('dayone.billing.provider', null);
    config()->set('dayone.resolver.strategies', []);
    config()->set('dayone.admin.path', null);

    $errors = $this->validator->validate();

    expect(count($errors))->toBeGreaterThanOrEqual(3);
});
