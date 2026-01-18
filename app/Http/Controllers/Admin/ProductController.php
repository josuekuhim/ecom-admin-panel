<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Drop;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ProductImage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $products = Product::with('drop')
            // Avoid cross-database count on images (stored in sqlite)
            ->withCount(['variants'])
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                             ->orWhere('description', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        // Compute images_count from sqlite_images and attach to each product
        $ids = $products->getCollection()->pluck('id');
        if ($ids->isNotEmpty()) {
            $imageCounts = ProductImage::on('sqlite_images')
                ->whereIn('product_id', $ids)
                ->selectRaw('product_id, COUNT(*) as aggregate')
                ->groupBy('product_id')
                ->pluck('aggregate', 'product_id');

            $products->getCollection()->transform(function ($product) use ($imageCounts) {
                $product->images_count = (int)($imageCounts[$product->id] ?? 0);
                return $product;
            });
        }

        return view('admin.products.index', compact('products', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $drops = Drop::all();
        return view('admin.products.create', compact('drops'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'drop_id' => 'required|exists:drops,id',
            'available' => 'boolean',
            'variants' => 'present|array',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.value' => 'required|string|max:255',
            'variants.*.stock' => 'required|integer|min:0',
            // optional cover image stored as BLOB
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
            'alt_text' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($validatedData, $request) {
                $product = Product::create([
                    'name' => $validatedData['name'],
                    'description' => $validatedData['description'],
                    'price' => $validatedData['price'],
                    'drop_id' => $validatedData['drop_id'],
                    'available' => $validatedData['available'] ?? true,
                ]);

                if (isset($validatedData['variants'])) {
                    $product->variants()->createMany($validatedData['variants']);
                }

                // Optional cover image -> persist as BLOB on sqlite_images
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $imageData = file_get_contents($file->getPathname());
                    $mimeType = $file->getMimeType();
                    $originalFilename = $file->getClientOriginalName();
                    $fileSize = $file->getSize();

                    $imageRecord = $product->images()->create([
                        'image_url' => null,
                        'image_data' => $imageData,
                        'mime_type' => $mimeType,
                        'original_filename' => $originalFilename,
                        'file_size' => $fileSize,
                        'alt_text' => $validatedData['alt_text'] ?? null,
                    ]);

                    if ($imageRecord) {
                        $imageRecord->update([
                            'image_url' => url("/api/images/product/{$imageRecord->id}")
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while creating the product: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('admin.products.index')->with('success', 'Product and variants created successfully.');
    }


    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load(['variants', 'images', 'drop']);
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $drops = Drop::all();
        // Eager load variants and images for the edit screen
        $product->load(['variants', 'images']);
        return view('admin.products.edit', compact('product', 'drops'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'drop_id' => 'required|exists:drops,id',
            'available' => 'boolean',
            'variants' => 'present|array',
            'variants.*.id' => 'nullable|exists:product_variants,id',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.value' => 'required|string|max:255',
            'variants.*.stock' => 'required|integer|min:0',
            // Support multiple images upload
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
            'alt_text' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($validatedData, $product, $request) {
                // Update product details
                $product->update([
                    'name' => $validatedData['name'],
                    'description' => $validatedData['description'],
                    'price' => $validatedData['price'],
                    'drop_id' => $validatedData['drop_id'],
                    'available' => $validatedData['available'] ?? true,
                ]);

                $incomingVariantIds = [];

                // Update existing variants and create new ones
                foreach ($validatedData['variants'] as $variantData) {
                    if (!empty($variantData['id'])) {
                        $variant = ProductVariant::find($variantData['id']);
                        if ($variant) {
                            $variant->update($variantData);
                            $incomingVariantIds[] = $variant->id;
                        }
                    } else {
                        $newVariant = $product->variants()->create($variantData);
                        $incomingVariantIds[] = $newVariant->id;
                    }
                }

                // Delete variants that were removed
                $product->variants()->whereNotIn('id', $incomingVariantIds)->delete();

                // Handle multiple images upload
                if ($request->hasFile('images')) {
                    $uploadedImages = $request->file('images');
                    
                    foreach ($uploadedImages as $index => $file) {
                        $imageData = file_get_contents($file->getPathname());
                        $mimeType = $file->getMimeType();
                        $originalFilename = $file->getClientOriginalName();
                        $fileSize = $file->getSize();

                        $imageRecord = $product->images()->create([
                            'image_url' => null,
                            'image_data' => $imageData,
                            'mime_type' => $mimeType,
                            'original_filename' => $originalFilename,
                            'file_size' => $fileSize,
                            // Apply alt_text only to first image
                            'alt_text' => ($index === 0 && isset($validatedData['alt_text'])) ? $validatedData['alt_text'] : null,
                        ]);

                        if ($imageRecord) {
                            $imageRecord->update([
                                'image_url' => url("/api/images/product/{$imageRecord->id}")
                            ]);
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while updating the product: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
