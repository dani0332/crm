<?php

namespace App\Services;

use App\Models\NationalityPool;

class NationalityPoolService
{
    public function getNationalityCodes(): ?NationalityPool
    {
        return NationalityPool::active()
            ->select('canonical_nationality_codes')
            ->first();
    }
}
