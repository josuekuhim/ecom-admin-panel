<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        return $product->variants;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'value' => 'required|max:255',
            'stock' => 'required|integer|min:0',
        ]);

        $variant = $product->variants()->create($validatedData);

        return response()->json($variant, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductVariant $variant)
    {
        return $variant;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductVariant $variant)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|max:255',
            'value' => 'sometimes|required|max:255',
            'stock' => 'sometimes|required|integer|min:0',
        ]);

        $variant->update($validatedData);

        return response()->json($variant);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductVariant $variant)
    {
        $variant->delete();

        return response()->json(null, 204);
    }
}
