<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use App\Models\DropImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ImageController extends Controller
{
    /**
     * Serve product image by ID
     */
    public function productImage($id)
    {
        $image = ProductImage::find($id);
        
        if (!$image || !$image->image_data) {
            abort(404);
        }

        return response($image->image_data)
            ->header('Content-Type', $image->mime_type ?? 'image/jpeg')
            ->header('Content-Length', $image->file_size ?? strlen($image->image_data))
            ->header('Cache-Control', 'public, max-age=31536000') // Cache for 1 year
            ->header('Expires', now()->addYear()->toRfc2822String());
    }

    /**
     * Serve drop image by ID
     */
    public function dropImage($id)
    {
        $image = DropImage::find($id);
        
        if (!$image || !$image->image_data) {
            abort(404);
        }

        return response($image->image_data)
            ->header('Content-Type', $image->mime_type ?? 'image/jpeg')
            ->header('Content-Length', $image->file_size ?? strlen($image->image_data))
            ->header('Cache-Control', 'public, max-age=31536000') // Cache for 1 year
            ->header('Expires', now()->addYear()->toRfc2822String());
    }
}
