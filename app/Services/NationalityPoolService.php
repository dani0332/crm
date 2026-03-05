<?php

namespace App\Services;

use App\Models\NationalityPool;

class NationalityPoolService
{
    public function get(int $nationalityId): ?NationalityPool
    {
        return NationalityPool::active()
            ->select('canonical_nationality_codes')
            ->first();
    }
}
