<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    public function partnerPlans(): HasMany
    {
        return $this->hasMany(PartnerPlan::class);
    }
}
