<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class ClaimHistory extends BaseModel
{
    protected $table = 'claim_history';
    use HasFactory;

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, 'claim_history', ['code', 'id', 'text']);
    }
}
