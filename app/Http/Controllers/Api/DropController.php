<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drop;
use App\Models\DropImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DropController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $drops = Drop::where('available', true)->orderByDesc('created_at')->get();

        // Attach first image from sqlite_images as cover image
        try {
            $ids = $drops->pluck('id');
            if ($ids->isNotEmpty()) {
                $images = DropImage::on('sqlite_images')
                    ->whereIn('drop_id', $ids)
                    ->orderBy('id')
                    ->get(['id','drop_id','image_url','mime_type','file_size','original_filename']);

                $firstByDrop = $images->groupBy('drop_id')->map->first();

                $drops->transform(function ($d) use ($firstByDrop) {
                    // Set relation for potential clients
                    $groupImages = $firstByDrop->has($d->id) ? collect([$firstByDrop[$d->id]]) : collect();
                    $d->setRelation('images', $groupImages);
                    // Backfill legacy image_url with the first image when empty
                    if (empty($d->image_url) && $firstByDrop->has($d->id)) {
                        $d->image_url = $firstByDrop[$d->id]['image_url'] ?? null;
                    }
                    return $d;
                });
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to load drop images from sqlite_images', ['error' => $e->getMessage()]);
        }

        return response()->json($drops);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'image_url' => 'nullable|url',
        ]);

        $drop = Drop::create($validatedData);

        return response()->json($drop, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Drop $drop)
    {
        // Eager load products with variants
        $drop->load(['products.variants']);
        
        // Attach images to drop and backfill image_url
        try {
            $images = DropImage::on('sqlite_images')
                ->where('drop_id', $drop->id)
                ->orderBy('id')
                ->get(['id','drop_id','image_url','mime_type','file_size','original_filename']);
            $drop->setRelation('images', $images);
            
            // Backfill legacy image_url with the first image when empty
            if (empty($drop->image_url) && $images->isNotEmpty()) {
                $drop->image_url = $images->first()->image_url;
            }
            
            // Ensure image_url has absolute path if it's relative
            if ($drop->image_url && !str_starts_with($drop->image_url, 'http://') && !str_starts_with($drop->image_url, 'https://')) {
                $drop->image_url = url($drop->image_url);
            }
            
        } catch (\Throwable $e) {
            Log::warning('Failed to load drop images (show) from sqlite_images', ['error' => $e->getMessage()]);
            $drop->setRelation('images', collect());
        }

        // Attach product images from sqlite_images to each product
        try {
            $productIds = $drop->products->pluck('id');
            if ($productIds->isNotEmpty()) {
                $productImages = \App\Models\ProductImage::on('sqlite_images')
                    ->whereIn('product_id', $productIds)
                    ->orderBy('id')
                    ->get(['id','product_id','image_url','mime_type','file_size','original_filename']);

                $grouped = $productImages->groupBy('product_id');
                
                $drop->products->transform(function ($p) use ($grouped) {
                    // Convert image objects to array of absolute URL strings
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
            Log::warning('Failed to load product images in drop show', ['error' => $e->getMessage()]);
            // Ensure all products have empty images array
            $drop->products->transform(function ($p) {
                $p->setRelation('images', collect());
                return $p;
            });
        }
        
        return response()->json($drop);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Drop $drop)
    {
        $validatedData = $request->validate([
            'title' => 'sometimes|required|max:255',
            'description' => 'sometimes|required',
            'image_url' => 'nullable|url',
        ]);

        $drop->update($validatedData);

        return response()->json($drop);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Drop $drop)
    {
        $drop->delete();

        return response()->json(null, 204);
    }
}
