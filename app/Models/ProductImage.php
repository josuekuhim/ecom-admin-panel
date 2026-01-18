<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    // Use a dedicated SQLite connection for images
    protected $connection = 'sqlite_images';

    protected $fillable = [
        'product_id',
        'image_url',
        'image_data',
        'mime_type',
        'original_filename',
        'file_size',
        'alt_text',
    ];

    public function product()
    {
        // Product belongs to a Postgres model; ensure foreign keys are logical only (no FK constraints across DBs)
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }
        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }
        if (Str::startsWith($value, ['/storage', 'storage'])) {
            $path = Str::startsWith($value, '/') ? $value : '/'.$value;
            return rtrim(config('app.url'), '/').$path;
        }
        return $value;
    }
}
