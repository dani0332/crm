<?php

namespace App\Services\Life;

use App\Models\LostReasons;
use App\Services\BaseService;

class LostReasonService extends BaseService
{
    public function getAllOrderedBy(string $orderByColumn, string $orderDirection)
    {
        return LostReasons::orderBy($orderByColumn, $orderDirection)->get();
    }
}
