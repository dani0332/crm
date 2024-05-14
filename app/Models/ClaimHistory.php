<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClaimHistory extends BaseModel
{
    protected $table = 'claim_history';

    use HasFactory;

    public function processGetDSL($filters)
    {
        return self::processGetBaseDSL($filters, 'claim_history', ['code', 'id', 'text']);
    }

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
