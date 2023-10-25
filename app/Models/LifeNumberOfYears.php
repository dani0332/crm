<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LifeNumberOfYears extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'life_number_of_year';

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }
}
