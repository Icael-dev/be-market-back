<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['id', 'user_id', 'email', 'full_name', 'phone', 'avatar_url', 'is_admin', 'role'])]
class Profile extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Profile $profile) {
            if (! $profile->id) {
                $profile->id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
