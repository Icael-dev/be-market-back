<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LegalPage extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'slug',
        'title',
        'content',
    ];

    protected static function booted(): void
    {
        static::creating(function (LegalPage $page) {
            if (!$page->id) {
                $page->id = (string) Str::uuid();
            }
        });
    }
}
