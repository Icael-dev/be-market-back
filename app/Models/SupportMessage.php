<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SupportMessage extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'ticket_id',
        'user_id',
        'message',
        'is_from_admin',
    ];

    protected $casts = [
        'is_from_admin' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportMessage $message) {
            if (!$message->id) {
                $message->id = (string) Str::uuid();
            }
        });
    }

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
