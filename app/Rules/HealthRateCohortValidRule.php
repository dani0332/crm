<?php

namespace App\Rules;

use App\Models\HealthPlan;
use App\Services\CohortMappingService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class HealthRateCohortValidRule implements ValidationRule
{
    public function __construct(protected ?int $healthPlanId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cohortMappingService = app(CohortMappingService::class);
        $healthPlan = $this->healthPlanId ? HealthPlan::find($this->healthPlanId) : null;

        if ($healthPlan && $healthPlan->cohort_enabled) {
            $cohorts = $cohortMappingService->getAllCohorts();

            if (! in_array(Str::upper($value), $cohorts, true)) {
                $fail('The cohort is invalid.');
            }
        }
    }
}
