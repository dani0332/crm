<?php

namespace App\Services;

use App\Models\Lookup;

class CohortMappingService extends BaseService
{
    public function getAllCohorts(): ?array
    {
        return Lookup::where('key', 'cohort-mapping')->distinct()->pluck('text')->toArray();
    }
}
