<?php

namespace App\Models;

use App\Enums\HealthPlanRateSheetStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'is_hidden',
        'is_active',
        'status',
        'version',
        'parent_id',
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
}
