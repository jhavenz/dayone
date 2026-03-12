<?php

declare(strict_types=1);

namespace DayOne\Runtime;

use DayOne\Contracts\Context\V1\ProductResolver;
use DayOne\Models\Product;
use Illuminate\Http\Request;

final class DefaultProductResolver implements ProductResolver
{
    public function resolve(Request $request): ?Product
    {
        /** @var string[] $enabledKeys */
        $enabledKeys = config('dayone.resolver.strategies', ['route', 'header', 'domain', 'path']);
        $strategies = $this->strategies($request);

        foreach ($enabledKeys as $key) {
            if (! isset($strategies[$key])) {
                continue;
            }

            $product = $strategies[$key]();

            if ($product !== null) {
                return $product;
            }
        }

        return null;
    }

    /** @return array<string, \Closure(): ?Product> */
    private function strategies(Request $request): array
    {
        return [
            'route' => function () use ($request): ?Product {
                $param = $request->route('product');

                if ($param instanceof Product) {
                    return $param;
                }

                if (is_string($param) && $param !== '') {
                    return Product::query()->where('slug', $param)->first();
                }

                return null;
            },

            'header' => function () use ($request): ?Product {
                $slug = $request->header('X-Product');

                if (! is_string($slug) || $slug === '') {
                    return null;
                }

                return Product::query()->where('slug', $slug)->first();
            },

            'domain' => function () use ($request): ?Product {
                $host = $request->getHost();

                return Product::query()->where('domain', $host)->first();
            },

            'path' => function () use ($request): ?Product {
                $path = '/' . ltrim($request->path(), '/');

                return Product::query()
                    ->whereNotNull('path_prefix')
                    ->get()
                    ->first(fn (Product $product): bool => str_starts_with($path, '/' . ltrim((string) $product->path_prefix, '/')));
            },
        ];
    }
}
