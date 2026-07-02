<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class HealthPlanFactory extends Factory
{
    protected $model = HealthPlan::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->bothify('PLAN-####')),
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
