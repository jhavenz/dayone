<?php

declare(strict_types=1);

namespace DayOne\Support;

final class ConfigValidator
{
    private const VALID_BILLING_PROVIDERS = ['stripe'];

    private const VALID_CONCERNS = ['billing', 'auth', 'admin'];

    private const REQUIRED_CONFIG_KEYS = [
        'resolver.strategies',
        'products.cache_ttl',
        'billing.provider',
        'billing.webhook_path',
        'admin.path',
        'admin.auth_guard',
        'openapi.enabled',
        'openapi.path',
        'plugins.auto_discover',
        'plugins.directory',
        'events.auto_discover',
        'ejection.concerns',
    ];

    /**
     * @return array<int, string>
     */
    public function validate(): array
    {
        $errors = [];

        $this->checkBillingProvider($errors);
        $this->checkResolverStrategies($errors);
        $this->checkAdminPath($errors);
        $this->checkEjectionConcerns($errors);
        $this->checkRequiredKeys($errors);

        return $errors;
    }

    /**
     * @param array<int, string> $errors
     */
    private function checkBillingProvider(array &$errors): void
    {
        $provider = config('dayone.billing.provider');

        if (! is_string($provider) || $provider === '') {
            $errors[] = 'billing.provider is not set.';
            return;
        }

        if (! in_array($provider, self::VALID_BILLING_PROVIDERS, true)) {
            $errors[] = "billing.provider '{$provider}' is not valid. Supported: " . implode(', ', self::VALID_BILLING_PROVIDERS);
        }
    }

    /**
     * @param array<int, string> $errors
     */
    private function checkResolverStrategies(array &$errors): void
    {
        $strategies = config('dayone.resolver.strategies');

        if (! is_array($strategies) || $strategies === []) {
            $errors[] = 'resolver.strategies must be a non-empty array.';
        }
    }

    /**
     * @param array<int, string> $errors
     */
    private function checkAdminPath(array &$errors): void
    {
        $path = config('dayone.admin.path');

        if (! is_string($path) || $path === '') {
            $errors[] = 'admin.path is not set.';
        }
    }

    /**
     * @param array<int, string> $errors
     */
    private function checkEjectionConcerns(array &$errors): void
    {
        /** @var mixed $concerns */
        $concerns = config('dayone.ejection.concerns');

        if (! is_array($concerns)) {
            $errors[] = 'ejection.concerns must be an array.';
            return;
        }

        foreach ($concerns as $concern) {
            if (! is_string($concern) || ! in_array($concern, self::VALID_CONCERNS, true)) {
                $errors[] = "ejection.concerns contains invalid value '{$concern}'. Valid: " . implode(', ', self::VALID_CONCERNS);
            }
        }
    }

    /**
     * @param array<int, string> $errors
     */
    private function checkRequiredKeys(array &$errors): void
    {
        foreach (self::REQUIRED_CONFIG_KEYS as $key) {
            if (config("dayone.{$key}") === null) {
                $errors[] = "Missing required config key: {$key}";
            }
        }
    }
}
