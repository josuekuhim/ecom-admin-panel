<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 12);
        $perPage = $perPage > 0 ? min($perPage, 50) : 12;

        $query = Product::with(['variants']);
        
        if ($request->has('drop_id')) {
            $query->where('drop_id', $request->drop_id);
        }
        
        if (!$request->boolean('include_unavailable', false)) {
            $query->where('available', true);
        }

        $products = $query->orderByDesc('created_at')->paginate($perPage);

        // Attach images from sqlite_images safely
        try {
            $ids = $products->getCollection()->pluck('id');
            if ($ids->isNotEmpty()) {
                $images = ProductImage::on('sqlite_images')
                    ->whereIn('product_id', $ids)
                    ->orderBy('id')
                    ->get(['id','product_id','image_url','mime_type','file_size','original_filename']);

                $grouped = $images->groupBy('product_id');
                $products->getCollection()->transform(function ($p) use ($grouped) {
                    // Map image objects to an array of absolute URL strings for the API shape the storefront expects
                    $urls = ($grouped[$p->id] ?? collect())->values()->pluck('image_url')->map(function ($u) {
                        if ($u && !str_starts_with($u, 'http://') && !str_starts_with($u, 'https://')) {
                            return url($u);
                        }
                        return $u;
                    })->values();

                    $p->setRelation('images', $urls);
                    return $p;
                });
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to load product images from sqlite_images', ['error' => $e->getMessage()]);
            // Ensure images is an empty array to keep API shape consistent
            $products->getCollection()->transform(function ($p) {
                $p->setRelation('images', collect());
                return $p;
            });
        }

        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'description' => 'required',
            'price' => 'required|numeric',
            'drop_id' => 'required|exists:drops,id',
        ]);

        $product = Product::create($validatedData);

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load(['variants', 'drop']);

        try {
            $images = ProductImage::on('sqlite_images')
                ->where('product_id', $product->id)
                ->orderBy('id')
                ->get(['id','product_id','image_url','mime_type','file_size','original_filename']);

            // Convert to array of absolute URL strings
            $urls = $images->pluck('image_url')->map(function ($u) {
                if ($u && !str_starts_with($u, 'http://') && !str_starts_with($u, 'https://')) {
                    return url($u);
                }
                return $u;
            })->values();

            $product->setRelation('images', $urls);
        } catch (\Throwable $e) {
            Log::warning('Failed to load product images (show) from sqlite_images', ['error' => $e->getMessage()]);
            $product->setRelation('images', collect());
        }

        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|max:255',
            'description' => 'sometimes|required',
            'price' => 'sometimes|required|numeric',
            'drop_id' => 'sometimes|required|exists:drops,id',
        ]);

        $product->update($validatedData);

        return response()->json($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
