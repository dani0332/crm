<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class GroupMedicalCategory extends Model
{
    protected $table = 'group_medical_category';

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeOrdered($query)
    {
        if (Schema::hasColumn($this->table, 'sort_order')) {
            return $query->orderBy('sort_order');
        }

        return $query->orderBy('text');
    }
}
