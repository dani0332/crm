<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CurrencyType extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'currency_type';

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }
}
