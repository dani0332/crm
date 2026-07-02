<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use App\Models\HealthRateControl;
use Illuminate\Database\Eloquent\Factories\Factory;

class HealthRateControlFactory extends Factory
{
    protected $model = HealthRateControl::class;

    public function definition(): array
    {
        return [
            'health_plan_id' => HealthPlan::factory(),
            'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
            'effective_from' => now()->addDay()->toDateString(),
            'effective_to' => null,
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

    public function effectiveToday(): static
    {
        return $this->state(['effective_from' => now()->toDateString()]);
    }

    public function effectiveYesterday(): static
    {
        return $this->state(['effective_from' => now()->subDay()->toDateString()]);
    }
}
