<?php

declare(strict_types=1);

namespace DayOne\Http\Middleware;

use Closure;
use DayOne\Contracts\Context\V1\ProductContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class RequireProductRole
{
    public function __construct(
        private readonly ProductContext $context,
    ) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $product = $this->context->requireProduct();

        $userRole = DB::table('dayone_user_products')
            ->where('user_id', $user->getAuthIdentifier())
            ->where('product_id', $product->id)
            ->value('role');

        if (! in_array($userRole, $roles, true)) {
            abort(403, 'Insufficient product role');
        }

        return $next($request);
    }
}
