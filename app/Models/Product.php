<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'price',
        'compare_price',
        'stock',
        'images',
        'specs',
        'is_featured',
        'is_active',
        'weight',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'stock' => 'integer',
        'images' => 'array',
        'specs' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'weight' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (!$product->id) {
                $product->id = (string) Str::uuid();
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
