<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMedicalNetwork extends Model
{
    protected $table = 'group_medical_networks';

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
