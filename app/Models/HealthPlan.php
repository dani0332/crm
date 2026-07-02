<?php

namespace App\Models;

use App\Enums\HealthPlanRateSheetStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HealthPlan extends Model
{
    use HasFactory;

    protected $table = 'health_plan';
    protected $fillable = [
        'code',
        'text',
        'text_ar',
        'provider_id',
        'health_business_type',
        'plan_type_id',
        'health_rating_eligibility_id',
        'health_network_id',
        'maf_link',
        'is_hidden',
        'is_active',
        'status',
        'version',
        'parent_id',
        'cohort_enabled',
        'gender_enabled',
        'marital_status_enabled',
    ];
    protected $attributes = [
        'status' => HealthPlanRateSheetStatusEnum::DRAFT,
        'version' => 1.0,
    ];

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id');
    }

    public function healthNetwork(): BelongsTo
    {
        return $this->belongsTo(HealthNetwork::class, 'health_network_id');
    }

    public function healthRatingEligibility(): BelongsTo
    {
        return $this->belongsTo(HealthRatingEligibility::class, 'health_rating_eligibility_id');
    }

    public function healthPlanType(): BelongsTo
    {
        return $this->belongsTo(HealthPlanType::class, 'plan_type_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(HealthRate::class, 'health_plan_id');
    }

    public function healthRateControls(): HasMany
    {
        return $this->hasMany(HealthRateControl::class, 'health_plan_id');
    }

    public function draftRateControl(): HasOne
    {
        return $this->hasOne(HealthRateControl::class, 'health_plan_id')
            ->where('status', HealthPlanRateSheetStatusEnum::DRAFT->value);
    }

    public function activeRateControl(): HasOne
    {
        return $this->hasOne(HealthRateControl::class, 'health_plan_id')
            ->where('status', HealthPlanRateSheetStatusEnum::ACTIVE->value);
    }
}
