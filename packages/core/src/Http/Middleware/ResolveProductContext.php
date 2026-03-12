<?php

declare(strict_types=1);

namespace DayOne\Http\Middleware;

use Closure;
use DayOne\Contracts\Context\V1\ProductResolver;
use DayOne\Runtime\ProductContextInstance;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveProductContext
{
    public function __construct(
        private readonly ProductContextInstance $context,
        private readonly ProductResolver $resolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $product = $this->resolver->resolve($request);

        if ($product !== null && ! $product->isActive) {
            abort(404, 'Product not found');
        }

        if ($product !== null) {
            $this->context->setProduct($product);

            foreach ($product->settingsArray as $key => $value) {
                config(["dayone.products.{$product->slug}.{$key}" => $value]);
            }
        }

        return $next($request);
    }
}
