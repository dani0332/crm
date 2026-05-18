<?php

namespace App\Services;

use App\Models\NationalityPool;
use Carbon\Carbon;

class NationalityPoolService
{
    public function getNationalityCodes(): ?NationalityPool
    {
        $today = Carbon::today()->toDateString();

        return NationalityPool::select('canonical_nationality_codes')
            ->whereDate('effective_from', '<=', $today)
            ->orderByDesc('effective_from')
            ->first();
    }
}
