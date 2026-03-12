<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\User;
use DayOne\Contracts\Auth\V1\AuthManager;
use DayOne\DTOs\SubscriptionStatus;
use DayOne\Models\Product;
use DayOne\Models\Subscription;

trait InteractsWithDayOne
{
    protected function seedProduct(string $name, string $slug, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'settings' => [],
        ], $overrides));
    }

    protected function seedSubscription(User $user, Product $product, SubscriptionStatus $status): Subscription
    {
        return Subscription::withoutGlobalScopes()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'stripe_subscription_id' => 'sub_test_' . fake()->unique()->uuid(),
            'stripe_price_id' => 'price_test_123',
            'status' => $status,
        ]);
    }

    /**
     * @return array{User, string}
     */
    protected function authenticatedUser(?string $productSlug = null): array
    {
        $user = User::factory()->create();
        $auth = app(AuthManager::class);

        if ($productSlug !== null) {
            $product = Product::where('slug', $productSlug)->firstOrFail();
            $context = app(\DayOne\Runtime\ProductContextInstance::class);
            $context->setProduct($product);
        }

        $tokenResult = $auth->issueToken($user, 'test-token');

        if ($productSlug !== null) {
            $context = app(\DayOne\Runtime\ProductContextInstance::class);
            // Reset context after token creation
            $reflection = new \ReflectionProperty($context, 'resolved');
            $reflection->setValue($context, null);
        }

        return [$user, $tokenResult->token];
    }

    protected function withProductHeader(string $slug): static
    {
        return $this->withHeader('X-Product', $slug);
    }

    protected function fakeStripeWebhook(string $type, array $data, ?string $secret = null): array
    {
        $secret = $secret ?? config('dayone.billing.webhook_secret', 'whsec_test_secret');
        $timestamp = time();

        $payload = json_encode([
            'id' => 'evt_test_' . fake()->unique()->uuid(),
            'type' => $type,
            'data' => ['object' => $data],
        ]);

        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return [
            'payload' => $payload,
            'headers' => [
                'Stripe-Signature' => "t={$timestamp},v1={$signature}",
                'Content-Type' => 'application/json',
            ],
        ];
    }
}
