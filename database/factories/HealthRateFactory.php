<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use App\Models\HealthRate;
use App\Models\HealthRateControl;
use Illuminate\Database\Eloquent\Factories\Factory;

class HealthRateFactory extends Factory
{
    protected $model = HealthRate::class;

    public function definition(): array
    {
        return [
            'health_plan_id' => HealthPlan::factory(),
            'health_rate_control_id' => HealthRateControl::factory(),
            'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
            'version' => 1.0,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(['status' => HealthPlanRateSheetStatusEnum::SCHEDULED->value]);
    }

    public function active(): static
    {
        return $this->state(['status' => HealthPlanRateSheetStatusEnum::ACTIVE->value]);
    }

    public function archived(): static
    {
        return $this->state(['status' => HealthPlanRateSheetStatusEnum::ARCHIVED->value]);
    }
}
