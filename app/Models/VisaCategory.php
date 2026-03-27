<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaCategory extends Model
{
    protected $table = 'visa_categories';
    protected $fillable = ['code', 'text', 'is_active', 'sort_order', 'health_cover_for_id'];

    public function scopeActive($query)
    {
        $query->where('is_active', true);
    }
}
