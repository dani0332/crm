<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\SpatieActivityLog;

class ClaimHistory extends BaseModel
{
    protected $table = 'claim_history';

    use HasFactory, SpatieActivityLog;

    /**
     * scope to get active records
     *
     * @return mixed
     */
    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }
}
