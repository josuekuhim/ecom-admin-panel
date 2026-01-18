<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DropImage extends Model
{
    // Use a dedicated SQLite connection for images
    protected $connection = 'sqlite_images';

    protected $fillable = [
        'drop_id',
        'image_url',
        'image_data',
        'mime_type',
        'original_filename',
        'file_size',
        'alt_text',
    ];

    public function drop()
    {
        return $this->belongsTo(Drop::class);
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
