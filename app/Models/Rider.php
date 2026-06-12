<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rider extends Model
{
    protected $table = 'rider';
    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function riderOptions()
    {
        return $this->hasMany(RiderOption::class, 'rider_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
