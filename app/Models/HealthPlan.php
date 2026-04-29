<?php

namespace App\Models;

use App\Enums\HealthPlanRateSheetStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthPlan extends Model
{
    use HasFactory;

    protected $table = 'health_plan';
    protected $attributes = [
        'status' => HealthPlanRateSheetStatusEnum::DRAFT,
    ];

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id');
    }

    public function healthNetwork()
    {
        return $this->belongsTo(HealthNetwork::class, 'health_network_id');
    }

}
