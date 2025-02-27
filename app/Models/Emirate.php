<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Emirate extends BaseModel
{
    use HasFactory;

    protected $table = 'emirates';

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }
}
