<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'category_id',
        'sku',
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'compare_price',
        'cost_price',
        'stock',
        'min_stock_alert',
        'images',
        'specs',
        'dimensions',
        'variants',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_featured',
        'is_active',
        'weight',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock' => 'integer',
        'min_stock_alert' => 'integer',
        'images' => 'array',
        'specs' => 'array',
        'dimensions' => 'array',
        'variants' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'weight' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (! $product->id) {
                $product->id = (string) Str::uuid();
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    protected function images(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $images = is_string($value) ? json_decode($value, true) : $value;
                if (! is_array($images)) {
                    return [];
                }

                return array_map(function ($url) {
                    if (is_string($url) && str_starts_with($url, '/storage/products/')) {
                        return str_replace('/storage/products/', '/api/images/', $url);
                    }
                    return $url;
                }, $images);
            },
        );
    }
}
