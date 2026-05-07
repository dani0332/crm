<?php

namespace App\Models;

use App\Enums\HealthPlanRateSheetStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRateControl extends Model
{
    protected $table = 'health_rates_control';
    protected $fillable = [
        'health_plan_id',
        'version',
        'status',
        'effective_from',
        'effective_to',
        'total_records',
        'file_name',
        'created_by',
    ];
    protected $attributes = [
        'status' => HealthPlanRateSheetStatusEnum::DRAFT,
        'version' => 1.0,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function healthPlan(): BelongsTo
    {
        return $this->belongsTo(HealthPlan::class, 'health_plan_id', 'id');
    }
}
