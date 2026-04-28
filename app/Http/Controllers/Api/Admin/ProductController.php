<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Product::with('category')->orderBy('name')->get());
    }

    public function categories(): JsonResponse
    {
        return response()->json(ProductCategory::orderBy('name')->get());
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required','string','max:100']]);
        return response()->json(ProductCategory::create($data), 201);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                => ['required','string','max:150'],
            'description'         => ['nullable','string'],
            'price'               => ['required','numeric','min:0'],
            'stock'               => ['required','integer','min:0'],
            'active'              => ['boolean'],
            'product_category_id' => ['nullable','exists:product_categories,id'],
            'image'               => ['nullable','image','max:2048'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products','public');
        }

        return response()->json(Product::create($data)->load('category'), 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'name'                => ['sometimes','required','string','max:150'],
            'description'         => ['nullable','string'],
            'price'               => ['sometimes','required','numeric','min:0'],
            'stock'               => ['sometimes','required','integer','min:0'],
            'active'              => ['boolean'],
            'product_category_id' => ['nullable','exists:product_categories,id'],
            'image'               => ['nullable','image','max:2048'],
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) Storage::disk('public')->delete($product->image);
            $data['image'] = $request->file('image')->store('products','public');
        }

        $product->update($data);
        return response()->json($product->load('category'));
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->image) Storage::disk('public')->delete($product->image);
        $product->delete();
        return response()->json(null, 204);
    }
}
