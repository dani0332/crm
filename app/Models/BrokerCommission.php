<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrokerCommission extends Model
{
    protected $table = 'broker_commissions';

    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }
}
