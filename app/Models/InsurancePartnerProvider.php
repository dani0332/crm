<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePartnerProvider extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function providerPlans(): HasMany
    {
        return $this->hasMany(InsurancePartnerProviderPlan::class, 'partner_provider_id');
    }

    public function scopeActiveForProvider(Builder $query, int $providerId, int $quoteTypeId): Builder
    {
        return $query->where('provider_id', $providerId)
            ->where('quote_type_id', $quoteTypeId)
            ->where('auto_issuance_enabled', true)
            ->where('is_active', true);
    }
}
