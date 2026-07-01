<?php

namespace App\Models;

use App\Enums\HealthPlanRateSheetStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthRateControl extends Model
{
    use HasFactory;

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
    protected $casts = [
        'status' => HealthPlanRateSheetStatusEnum::class,
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

    public function rates(): HasMany
    {
        return $this->hasMany(HealthRate::class, 'health_rate_control_id', 'id');
    }
}
