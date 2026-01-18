<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DropImage> $images
 * @property-read int $products_count
 * @property-read int $images_count
 */
class Drop extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_url', // legacy single image support
        'available',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(DropImage::class);
    }

    // Ensure legacy image_url is absolute (same behavior as Product images)
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
