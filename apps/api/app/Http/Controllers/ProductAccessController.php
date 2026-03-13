<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use DayOne\Contracts\Auth\V1\AuthManager;
use DayOne\Contracts\Context\V1\ProductContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ProductAccessController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly ProductContext $context,
    ) {}

    public function grant(Request $request): JsonResponse
    {
        $product = $this->context->requireProduct();
        $role = $request->input('role', 'user');

        $this->authorizeGrant($request, $product);

        $this->auth->grantProductAccess($request->user(), $product, $role);

        return response()->json(['message' => 'Access granted']);
    }

    public function revoke(Request $request): JsonResponse
    {
        $product = $this->context->requireProduct();

        $this->authorizeAdmin($request, $product);

        $this->auth->revokeProductAccess($request->user(), $product);

        return response()->json(['message' => 'Access revoked']);
    }

    public function check(Request $request): JsonResponse
    {
        $product = $this->context->requireProduct();
        $hasAccess = $this->auth->hasProductAccess($request->user(), $product);

        return response()->json(['has_access' => $hasAccess]);
    }

    private function authorizeGrant(Request $request, \DayOne\Models\Product $product): void
    {
        $hasAnyUsers = DB::table('dayone_user_products')
            ->where('product_id', $product->id)
            ->exists();

        if (! $hasAnyUsers) {
            return;
        }

        $this->authorizeAdmin($request, $product);
    }

    private function authorizeAdmin(Request $request, \DayOne\Models\Product $product): void
    {
        $userRole = DB::table('dayone_user_products')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('product_id', $product->id)
            ->value('role');

        if ($userRole !== 'admin') {
            abort(403, 'Insufficient product role');
        }
    }
}
