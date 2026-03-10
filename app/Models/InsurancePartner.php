<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePartner extends Model
{
    protected $fillable = [
        'name',
        'code',
        'email',
        'is_active',
    ];

    public function partnerProviders(): HasMany
    {
        return $this->hasMany(InsurancePartnerProvider::class, 'partner_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '');
    }

    public function scopeForCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    public function scopeHasActiveProvider(Builder $query, int $providerId, int $quoteTypeId): Builder
    {
        return $query->whereHas('partnerProviders', fn (Builder $q) => $q->activeForProvider($providerId, $quoteTypeId));
    }

    public function scopeHasActivePlan(Builder $query, int $planId): Builder
    {
        return $query->whereHas('partnerProviders.providerPlans', fn (Builder $q) => $q->activeForPlan($planId));
    }
}
