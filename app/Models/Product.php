<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

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
        'condition',
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
        'condition' => 'string',
    ];

    protected $appends = [
        'effective_price',
        'flash_discount_percentage',
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

    public function flashSales()
    {
        return $this->hasMany(FlashSale::class);
    }

    /**
     * Currently running flash sale for this product, if any.
     */
    public function activeFlashSale()
    {
        $now = now();

        return $this->hasOne(FlashSale::class)
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->orderByDesc('discount_percentage');
    }

    /**
     * Discount percentage from the active flash sale (0 when none). Authoritative.
     */
    public function activeFlashDiscount(): int
    {
        $sale = $this->relationLoaded('activeFlashSale')
            ? $this->getRelation('activeFlashSale')
            : $this->activeFlashSale()->first();

        if (! $sale) {
            return 0;
        }

        return max(0, min(99, (int) $sale->discount_percentage));
    }

    /**
     * Server-side authoritative unit price after applying any active flash sale.
     */
    public function effectivePriceValue(): float
    {
        $base = (float) $this->price;
        $discount = $this->activeFlashDiscount();

        if ($discount <= 0) {
            return round($base, 2);
        }

        return round($base * (1 - $discount / 100), 2);
    }

    protected function effectivePrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->effectivePriceValue(),
        );
    }

    protected function flashDiscountPercentage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->activeFlashDiscount(),
        );
    }
}
