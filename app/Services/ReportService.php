<?php

namespace App\Services;

use Carbon\Carbon;

class ReportService extends BaseService
{
    protected $query;
    protected $searchPrefix = 'r.';

    public function generateBatchesFilterText()
    {
        $batchArray = [];
        $startDate = Carbon::parse('2018-08-05')->startOfYear();
        $count = 1;
        while ($startDate < now()) {
            $currentDate = $startDate->toDateString();
            $nextWeek = $startDate->addDays(7)->toDateString();
            $key = $currentDate.','.$nextWeek;
            $value = 'Batch - '.$count.' -('.$currentDate.'to'.$nextWeek.')';
            array_push($batchArray, $key.'|'.$value);
            $count++;
        }

        return $batchArray;
    }
}
