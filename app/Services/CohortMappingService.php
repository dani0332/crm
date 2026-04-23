<?php

namespace App\Services;

use App\Models\CohortMapping;

class CohortMappingService extends BaseService
{
    public function getAllCohorts(): ?array
    {
        return CohortMapping::distinct()->pluck('cohort')->toArray();
    }
}
