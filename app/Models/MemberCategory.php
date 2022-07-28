<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberCategory extends Model
{
    use HasFactory;

    protected $table = 'member_category';

    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }

    public function scopeSortOrderAsc($query)
    {
        $query->orderBy('sort_order', 'asc');
    }

    public function scopeSortOrderDesc($query)
    {
        $query->orderBy('sort_order', 'desc');
    }
}
