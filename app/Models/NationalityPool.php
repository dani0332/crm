<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NationalityPool extends Model
{
    protected $table = 'nationality_pool';
    protected $fillable = [
        'effective_from',
        'effective_to',
        'canonical_nationality_codes',
        'health_nationality_group_ids',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
