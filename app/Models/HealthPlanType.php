<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class HealthPlanType extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'health_plan_type';

    public function scopeForGroupMedical($query)
    {
        if (Schema::hasColumn($this->table, 'type')) {
            $query->where('type', 'group');
        }

        if (Schema::hasColumn($this->table, 'is_active')) {
            $query->where('is_active', 1);
        }

        return $query;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
