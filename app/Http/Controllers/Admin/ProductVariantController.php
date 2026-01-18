<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Not needed as variants are shown in product show page
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Product $product)
    {
        return view('admin.variants.create', compact('product'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'stock' => 'required|integer|min:0',
        ]);

        $product->variants()->create($validatedData);

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Variant created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductVariant $variant)
    {
        // Not needed as variants are shown in product show page
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductVariant $variant)
    {
        return view('admin.variants.edit', compact('variant'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductVariant $variant)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'stock' => 'required|integer|min:0',
        ]);

        $variant->update($validatedData);

        return redirect()->route('admin.products.show', $variant->product)
            ->with('success', 'Variant updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductVariant $variant)
    {
        $product = $variant->product;
        $variant->delete();

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Variant deleted successfully.');
    }
}
