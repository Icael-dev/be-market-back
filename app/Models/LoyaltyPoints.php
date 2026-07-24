<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoyaltyPoints extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'points',
        'total_earned',
        'total_redeemed',
        'points_expire_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'total_earned' => 'integer',
        'total_redeemed' => 'integer',
        'points_expire_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (LoyaltyPoints $loyaltyPoints) {
            if (! $loyaltyPoints->id) {
                $loyaltyPoints->id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(LoyaltyTransaction::class, 'user_id', 'user_id');
    }
}
