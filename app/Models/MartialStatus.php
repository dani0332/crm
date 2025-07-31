<?php

namespace App\Models;

use App\Traits\Modelable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class MartialStatus extends Model implements AuditableContract
{
    use Auditable, HasFactory, Modelable;

    protected $table = 'marital_status';

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }
}
