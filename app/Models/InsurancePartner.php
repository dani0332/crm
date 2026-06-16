<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePartner extends Model
{
    use HasFactory;

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

    public function scopeHasActiveProviderWithPlan(Builder $query, int $providerId, int $quoteTypeId, ?int $planId): Builder
    {
        return $query->whereHas('partnerProviders', function (Builder $q) use ($providerId, $quoteTypeId, $planId) {
            $q->where('provider_id', $providerId)
                ->where('quote_type_id', $quoteTypeId)
                ->where('auto_issuance_enabled', true)
                ->where('is_active', true);

            if ($planId !== null) {
                $q->whereHas('providerPlans', fn (Builder $planQuery) => $planQuery->where('plan_id', $planId)->where('is_active', true));
            }
        });
    }
}
