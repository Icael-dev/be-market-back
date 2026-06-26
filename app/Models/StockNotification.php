<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StockNotification extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'product_id',
        'email',
        'is_notified',
    ];

    protected $casts = [
        'is_notified' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockNotification $notification) {
            if (!$notification->id) {
                $notification->id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
