<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LifeInsuranceTenure extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'life_insurance_tenure';

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }
}
