<?php

namespace App\Builders;

use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;

trait QueryBuildable
{
    use TeamHierarchyTrait;

    protected function parseDate($date, $isStartOfDay)
    {
        if ($date && $date != '') {
            if ($isStartOfDay) {
                return Carbon::parse($date)->startOfDay()->toDateTimeString();
            } else {
                return Carbon::parse($date)->endOfDay()->toDateTimeString();
            }
        }
    }
}
