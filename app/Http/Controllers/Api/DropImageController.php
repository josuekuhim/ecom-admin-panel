<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drop;
use App\Models\DropImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DropImageController extends Controller
{
    public function index(Drop $drop)
    {
        return $drop->images;
    }

    public function store(Request $request, Drop $drop)
    {
        $validated = $request->validate([
            'image' => 'required_without:image_url|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
            'image_url' => 'nullable|required_without:image|url',
            'alt_text' => 'nullable|max:255',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('drop-images', 'public');
            $imageUrl = '/storage/' . ltrim($path, '/');
        } elseif (!empty($validated['image_url'])) {
            $imageUrl = $validated['image_url'];
        }
        if (!$imageUrl) {
            return response()->json(['message' => 'Either image file or image_url is required'], 400);
        }

        $image = $drop->images()->create([
            'image_url' => $imageUrl,
            'alt_text' => $validated['alt_text'] ?? null,
        ]);

        return response()->json($image, 201);
    }

    public function show(DropImage $image)
    {
        return $image;
    }

    public function update(Request $request, DropImage $image)
    {
        $validated = $request->validate([
            'image_url' => 'sometimes|required|url',
            'alt_text' => 'nullable|max:255',
        ]);
        $image->update($validated);
        return response()->json($image);
    }

    public function destroy(DropImage $image)
    {
        $image->delete();
        return response()->json(null, 204);
    }
}
