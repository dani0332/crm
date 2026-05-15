<?php

namespace App\Models;

use App\Enums\HealthPlanRateSheetStatusEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'health_plan_id',
        'health_rate_control_id',
        'version',
        'health_plan_co_payment_id',
        'text',
        'text_ar',
        'emirate_type',
        'min_age',
        'max_age',
        'gender',
        'marital_status',
        'cohort',
        'premium',
        'status',
        'is_active',
    ];
    protected $attributes = [
        'status' => HealthPlanRateSheetStatusEnum::DRAFT,
        'version' => 1.0,
    ];

    protected function cohort(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value === null ? null : strtoupper($value),
        );

    }

    public function healthPlan(): BelongsTo
    {
        return $this->belongsTo(HealthPlan::class, 'health_plan_id');
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id');
    }

    public function healthRatingEligibility(): BelongsTo
    {
        return $this->belongsTo(HealthRatingEligibility::class, 'health_rating_eligibility_id');
    }

    public function healthPlanCoPayment(): BelongsTo
    {
        return $this->belongsTo(HealthPlanCoPayment::class, 'health_plan_co_payment_id');
    }

    public function healthRateControl(): BelongsTo
    {
        return $this->belongsTo(HealthRateControl::class, 'health_rate_control_id');
    }
}
