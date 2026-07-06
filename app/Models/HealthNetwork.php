<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthNetwork extends Model
{
    use HasFactory;

    protected $table = 'health_networks';

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
