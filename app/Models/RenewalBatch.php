<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalBatch extends Model
{
    use HasFactory, FilterCriteria;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function deadline()
    {
        return $this->hasOne(RenewalBatchDeadline::class);
    }
}
