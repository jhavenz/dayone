<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use DayOne\Contracts\Billing\V1\BillingManager;
use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\DTOs\CheckoutType;
use DayOne\DTOs\PlanDefinition;
use DayOne\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly BillingManager $billing,
        private readonly ProductContext $context,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'string'],
            'plan_name' => ['string'],
            'price' => ['integer', 'min:0'],
        ]);

        $plan = new PlanDefinition(
            id: $validated['plan_id'],
            name: $validated['plan_name'] ?? 'Default',
            priceInCents: $validated['price'] ?? 0,
            currency: 'usd',
            interval: 'month',
            features: [],
        );

        $checkout = $this->billing->createCheckout($plan, $request->user(), CheckoutType::Subscription);

        return response()->json(['checkout' => [
            'id' => $checkout->id,
            'url' => $checkout->url,
            'status' => $checkout->status,
        ]], 201);
    }

    public function show(Request $request, string $product): JsonResponse
    {
        $validated = $request->validate([
            'subscription_id' => ['required', 'string'],
        ]);

        $subscription = $this->resolveSubscription($request, $validated['subscription_id']);
        $status = $this->billing->getSubscriptionStatus($subscription);

        return response()->json(['status' => $status->value]);
    }

    public function cancel(Request $request, string $product): JsonResponse
    {
        $validated = $request->validate([
            'subscription_id' => ['required', 'string'],
        ]);

        $subscription = $this->resolveSubscription($request, $validated['subscription_id']);
        $this->billing->cancelSubscription($subscription);

        return response()->json(['message' => 'Subscription canceled']);
    }

    public function resume(Request $request, string $product): JsonResponse
    {
        $validated = $request->validate([
            'subscription_id' => ['required', 'string'],
        ]);

        $subscription = $this->resolveSubscription($request, $validated['subscription_id']);
        $this->billing->resumeSubscription($subscription);

        return response()->json(['message' => 'Subscription resumed']);
    }

    public function pause(Request $request, string $product): JsonResponse
    {
        $validated = $request->validate([
            'subscription_id' => ['required', 'string'],
        ]);

        $subscription = $this->resolveSubscription($request, $validated['subscription_id']);
        $this->billing->pauseSubscription($subscription);

        return response()->json(['message' => 'Subscription paused']);
    }

    private function resolveSubscription(Request $request, string $subscriptionId): Subscription
    {
        return Subscription::withoutGlobalScopes()
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('product_id', $this->context->requireProduct()->id)
            ->findOrFail($subscriptionId);
    }
}
