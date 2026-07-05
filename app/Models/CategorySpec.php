<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategorySpec extends Model
{
    protected $fillable = ['category_id', 'key', 'placeholder'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
