<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Drop;
use App\Models\DropImage;
use Illuminate\Http\Request;

class DropController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $drops = Drop::latest()->paginate(10);
        return view('admin.drops.index', compact('drops'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.drops.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'available' => 'boolean',
            // optional cover image stored as BLOB
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
        ]);

        $drop = Drop::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'available' => $validated['available'] ?? true,
        ]);

        // Optional cover image -> persist as BLOB on sqlite_images
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $imageData = file_get_contents($file->getPathname());
            $mimeType = $file->getMimeType();
            $originalFilename = $file->getClientOriginalName();
            $fileSize = $file->getSize();

            $imageRecord = $drop->images()->create([
                'image_url' => null,
                'image_data' => $imageData,
                'mime_type' => $mimeType,
                'original_filename' => $originalFilename,
                'file_size' => $fileSize,
                'alt_text' => null,
            ]);

            if ($imageRecord) {
                assert($imageRecord instanceof \App\Models\DropImage);
                $imageRecord->update([
                    'image_url' => url("/api/images/drop/{$imageRecord->id}")
                ]);
                // Backfill legacy cover for convenience
                $drop->update(['image_url' => $imageRecord->image_url]);
            }
        }

        return redirect()->route('admin.drops.index')->with('success', 'Drop created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Drop $drop)
    {
        return view('admin.drops.show', compact('drop'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Drop $drop)
    {
        return view('admin.drops.edit', compact('drop'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Drop $drop)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'available' => 'boolean',
            // optional new cover image -> store as BLOB
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
        ]);

        $drop->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'available' => $validated['available'] ?? true,
        ]);

        // Optional new cover image -> persist as BLOB and set as cover
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $imageData = file_get_contents($file->getPathname());
            $mimeType = $file->getMimeType();
            $originalFilename = $file->getClientOriginalName();
            $fileSize = $file->getSize();

            $imageRecord = $drop->images()->create([
                'image_url' => null,
                'image_data' => $imageData,
                'mime_type' => $mimeType,
                'original_filename' => $originalFilename,
                'file_size' => $fileSize,
                'alt_text' => null,
            ]);

            if ($imageRecord) {
                assert($imageRecord instanceof \App\Models\DropImage);
                $imageRecord->update([
                    'image_url' => url("/api/images/drop/{$imageRecord->id}")
                ]);
                // Set as drop cover for legacy consumers
                $drop->update(['image_url' => $imageRecord->image_url]);
            }
        }

        return redirect()->route('admin.drops.index')->with('success', 'Drop updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Drop $drop)
    {
        $drop->delete();

        return redirect()->route('admin.drops.index')->with('success', 'Drop deleted successfully.');
    }
}
