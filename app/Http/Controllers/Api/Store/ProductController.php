<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::with('category')
            ->where('active', true)
            ->when($request->filled('category_id'), fn ($q) =>
                $q->where('product_category_id', $request->integer('category_id'))
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->orderBy('name')
            ->get();

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        abort_if(!$product->active, 404);
        return response()->json($product->load('category'));
    }
}
