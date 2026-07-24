<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Banner extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'subtitle',
        'image_url',
        'link_url',
        'button_text',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Banner $banner) {
            if (! $banner->id) {
                $banner->id = (string) Str::uuid();
            }
        });
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (is_string($value) && str_starts_with($value, '/storage/products/')) {
                    return str_replace('/storage/products/', '/api/images/', $value);
                }

                return $value;
            },
        );
    }
}
