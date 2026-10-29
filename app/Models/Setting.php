<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Setting extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (Setting $setting) {
            if (! $setting->id) {
                $setting->id = (string) Str::uuid();
            }
        });
    }
}
