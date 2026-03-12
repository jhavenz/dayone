<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use DayOne\Contracts\Auth\V1\AuthManager;
use DayOne\Contracts\Context\V1\ProductContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $this->auth->grantProductAccess($request->user(), $product, $role);

        return response()->json(['message' => 'Access granted']);
    }

    public function revoke(Request $request): JsonResponse
    {
        $product = $this->context->requireProduct();

        $this->auth->revokeProductAccess($request->user(), $product);

        return response()->json(['message' => 'Access revoked']);
    }

    public function check(Request $request): JsonResponse
    {
        $product = $this->context->requireProduct();
        $hasAccess = $this->auth->hasProductAccess($request->user(), $product);

        return response()->json(['has_access' => $hasAccess]);
    }
}
