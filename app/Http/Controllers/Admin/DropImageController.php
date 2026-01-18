<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Drop;
use App\Models\DropImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class DropImageController extends Controller
{
    public function create(Drop $drop)
    {
        return view('admin.drop_images.create', compact('drop'));
    }

    public function store(Request $request, Drop $drop)
    {
        $request->validate([
            'image' => 'required_without:image_url|file|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
            'image_url' => 'nullable|required_without:image|url',
            'alt_text' => 'nullable|string|max:255',
        ]);

        $imageData = null;
        $mimeType = null;
        $originalFilename = null;
        $fileSize = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $imageData = file_get_contents($file->getPathname());
            $mimeType = $file->getMimeType();
            $originalFilename = $file->getClientOriginalName();
            $fileSize = $file->getSize();
        } elseif ($request->filled('image_url')) {
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
                if ($fileSize > 10 * 1024 * 1024) {
                    return back()->withErrors(['image_url' => 'Image exceeds 10MB limit.'])->withInput();
                }
                $mimeType = $response->header('Content-Type');
                if (!$mimeType) {
                    if (class_exists('finfo')) {
                        $finfo = new \finfo(\FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->buffer($imageData) ?: null;
                    }
                }
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
                $allowedMimes = [ 'image/jpeg','image/png','image/gif','image/webp','image/svg+xml' ];
                if (!in_array($mimeType, $allowedMimes)) {
                    return back()->withErrors(['image_url' => 'Unsupported image MIME type: '.$mimeType])->withInput();
                }
                $originalFilename = $basename;
            } catch (\Throwable $e) {
                return back()->withErrors(['image_url' => 'Error downloading image: '.$e->getMessage()])->withInput();
            }
        }

        if (!$imageData) {
            return back()->withErrors(['image' => 'No image data provided.'])->withInput();
        }

        $imageRecord = $drop->images()->create([
            'image_url' => null,
            'image_data' => $imageData,
            'mime_type' => $mimeType,
            'original_filename' => $originalFilename,
            'file_size' => $fileSize,
            'alt_text' => $request->alt_text,
        ]);

        if ($imageRecord) {
            assert($imageRecord instanceof \App\Models\DropImage);
            $imageRecord->update([
                'image_url' => url("/api/images/drop/{$imageRecord->id}")
            ]);
        }

        return redirect()->route('admin.drops.show', $drop)->with('success', 'Image added successfully.');
    }

    public function destroy(DropImage $image)
    {
        $drop = $image->drop;
        $image->delete();
        return redirect()->route('admin.drops.show', $drop)->with('success', 'Image deleted successfully.');
    }
}
