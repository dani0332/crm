<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InsurancePartnerMapping extends Model
{
    protected $table = 'insurance_partner_lead_data_mappers';

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
