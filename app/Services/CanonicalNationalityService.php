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

    public function getByCodes(string $codes): array
    {
        return CanonicalNationality::whereIn('canonical_nationality_code', explode(',', $codes))
            ->where('nationality_synonym', 0)
            ->pluck('canonical_nationality_name')
            ->toArray();
    }
}
