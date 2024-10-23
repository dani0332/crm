<?php

namespace App\Console\Commands\Common;

use App\Models\RenewalBatch;
use Carbon\Carbon;

trait Batchable
{
    protected function logTodayDate($type = '')
    {
        info('today date for '.$type.' batch job is : '.json_encode(now()->toDateString()));
    }

    protected function processBatchesFromScratch($startDate, $type = '')
    {
        info('inside creating '.$type.' batches from scratch');
        $batches = $this->generateBatchNumbers(Carbon::parse($startDate));
        $this->insertBatches($batches, $type);
    }

    protected function processBatchesFromLastEndDate($lastBatch, $type = '')
    {
        info('inside creating '.$type.' batch of current week');
        $batches = $this->generateBatchNumbers(Carbon::parse($lastBatch->end_date)->addDays(1));
        $this->insertBatches($batches, $type);
    }

    protected function isBatchCurrent($lastBatch)
    {
        return now()->startOfDay() >= Carbon::parse($lastBatch->start_date)->startOfDay()
            && now()->endOfDay() <= Carbon::parse($lastBatch->end_date)->endOfDay();
    }

    protected function insertBatches($batches, $type = '')
    {
        if (count($batches) > 0) {
            foreach ($batches as $batch) {
                $this->insertQuoteBatch($batch);
            }
            info($type.' batches created');
        }
    }

    protected function insertQuoteBatch($batch)
    {
        RenewalBatch::insert([
            'name' => $batch['name'],
            'start_date' => $batch['startDate'],
            'end_date' => $batch['endDate'],
            'month' => $batch['month'],
            'year' => $batch['year'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function generateBatchNumbers($startDate)
    {
        $batchArray = [];

        while ($startDate < now()) {
            $currentDate = $startDate->copy();
            $nextWeek = $startDate->copy()->addDays(6);

            $monthNumber = $currentDate->format('m');
            $fullYear = $currentDate->format('Y');
            $weekNumber = $currentDate->weekOfYear;

            $batchArray[] = [
                'name' => 'W'.$weekNumber,
                'startDate' => $currentDate->toDateString(),
                'endDate' => $nextWeek->toDateString(),
                'month' => $monthNumber,
                'year' => $fullYear,
            ];

            $startDate->addWeek();
        }

        return $batchArray;
    }
}
