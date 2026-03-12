<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use DayOne\Models\Product;
use Illuminate\Http\JsonResponse;

final class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::where('is_active', true)->get();

        return response()->json(['products' => $products]);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json(['product' => $product]);
    }
}
