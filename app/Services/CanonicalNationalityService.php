<?php

namespace App\Services;

use App\Models\CanonicalNationality;

class CanonicalNationalityService
{
    public function getByNationalityId(int $nationalityId): ?CanonicalNationality
    {
        return CanonicalNationality::where('nationality_id', $nationalityId)
            ->select('canonical_nationality_code')->first();
    }
}
