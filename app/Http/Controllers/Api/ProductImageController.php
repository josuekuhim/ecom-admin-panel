<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        return $product->images;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'image' => 'required_without:image_url|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'image_url' => 'nullable|required_without:image|url',
            'alt_text' => 'nullable|max:255',
        ]);

        $imageUrl = null;

        // Handle file upload
        if ($request->hasFile('image')) {
            // Store the file in 'storage/app/public/product-images'
            $path = $request->file('image')->store('product-images', 'public');
            // Use a relative public path
            $imageUrl = '/storage/' . ltrim($path, '/');
        }
        // Handle external URL
        elseif ($request->filled('image_url')) {
            $imageUrl = $request->image_url;
        }

        if (!$imageUrl) {
            return response()->json(['message' => 'Either image file or image_url is required'], 400);
        }

        $image = $product->images()->create([
            'image_url' => $imageUrl,
            'alt_text' => $validatedData['alt_text'] ?? null,
        ]);

        return response()->json($image, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductImage $image)
    {
        return $image;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductImage $image)
    {
        $validatedData = $request->validate([
            'image_url' => 'sometimes|required|url',
            'alt_text' => 'nullable|max:255',
        ]);

        $image->update($validatedData);

        return response()->json($image);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductImage $image)
    {
        $image->delete();

        return response()->json(null, 204);
    }
}
