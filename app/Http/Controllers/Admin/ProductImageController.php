<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class ProductImageController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(Product $product)
    {
        return view('admin.images.create', compact('product'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Product $product)
    {
        $request->validate([
            // Accept either uploaded file or external URL
            'image' => 'required_without:image_url|file|mimes:jpeg,png,jpg,gif,svg,webp|max:10240', // 10MB
            'image_url' => 'nullable|required_without:image|url',
            'alt_text' => 'nullable|string|max:255',
        ]);

        $imageData = null;
        $mimeType = null;
        $originalFilename = null;
        $fileSize = null;

        // Handle file upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $imageData = file_get_contents($file->getPathname());
            $mimeType = $file->getMimeType();
            $originalFilename = $file->getClientOriginalName();
            $fileSize = $file->getSize();
        }
        // Handle external URL by downloading and storing as BLOB
        elseif ($request->filled('image_url')) {
            $url = $request->input('image_url');
            try {
                $response = Http::timeout(20)->get($url);
                if (!$response->ok()) {
                    return back()->withErrors(['image_url' => 'Failed to download image from URL (HTTP '.$response->status().')'])->withInput();
                }
                $imageData = $response->body();
                $fileSize = strlen($imageData);
                if ($fileSize <= 0) {
                    return back()->withErrors(['image_url' => 'Downloaded image is empty.'])->withInput();
                }
                // Enforce max size 10MB
                if ($fileSize > 10 * 1024 * 1024) {
                    return back()->withErrors(['image_url' => 'Image exceeds 10MB limit.'])->withInput();
                }
                // Determine mime type safely
                $mimeType = $response->header('Content-Type');
                if (!$mimeType) {
                    if (class_exists('finfo')) {
                        $finfo = new \finfo(\FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->buffer($imageData) ?: null;
                    }
                }
                // Fallback by extension
                $path = parse_url($url, PHP_URL_PATH) ?? '';
                $basename = basename($path) ?: 'image';
                if (!$mimeType) {
                    $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
                    $map = [
                        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
                        'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml'
                    ];
                    $mimeType = $map[$ext] ?? 'application/octet-stream';
                }
                // Basic allowlist for image mimes
                $allowedMimes = [
                    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'
                ];
                if (!in_array($mimeType, $allowedMimes)) {
                    return back()->withErrors(['image_url' => 'Unsupported image MIME type: '.$mimeType])->withInput();
                }
                // Guess filename from URL
                $originalFilename = $basename;
            } catch (\Throwable $e) {
                return back()->withErrors(['image_url' => 'Error downloading image: '.$e->getMessage()])->withInput();
            }
        }

        // Guard against missing data
        if (!$imageData) {
            return back()->withErrors(['image' => 'No image data provided.'])->withInput();
        }

        // Save the image record to the database with BLOB data
        $imageRecord = $product->images()->create([
            'image_url' => null, // will be set after ID is known
            'image_data' => $imageData,
            'mime_type' => $mimeType,
            'original_filename' => $originalFilename,
            'file_size' => $fileSize,
            'alt_text' => $request->alt_text,
        ]);

        // Update image_url with the API endpoint
        if ($imageRecord) {
            assert($imageRecord instanceof \App\Models\ProductImage);
            $imageRecord->update([
                'image_url' => url("/api/images/product/{$imageRecord->id}")
            ]);
        }

        return redirect()->route('admin.products.edit', $product)->with('success', 'Image added successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductImage $image)
    {
        $productId = $image->product_id;

        $image->delete();

        return redirect()->route('admin.products.edit', $productId)->with('success', 'Image deleted successfully.');
    }
}
