<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'order_id',
        'user_id',
        'payment_method',
        'amount',
        'status',
        'reference',
        'phone_number',
        'provider_response',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'provider_response' => 'array',
    ];

    protected $hidden = [
        'phone_number',
        'provider_response',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (! $payment->id) {
                $payment->id = (string) Str::uuid();
            }
            if (! $payment->user_id && $payment->order_id) {
                $order = Order::find($payment->order_id);
                $payment->user_id = $order?->user_id;
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
