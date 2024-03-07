<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyIssuanceStatus extends Model
{
    use HasFactory;

    protected $table = 'policy_issuance_status';

    /**
     * @return $query
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
