<?php

namespace App\Console\Commands\Common;

use App\Models\RenewalBatch;
use Carbon\Carbon;
use Exception;

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

    protected function processBatchesFromLastEndDate($lastBatch = null, $type = '')
    {
        info('inside creating '.$type.' batch of current week');
        $batches = $this->generateBatchNumbers($lastBatch ? Carbon::parse($lastBatch->end_date)->addDays(1) : null);
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

    protected function generateBatchNumbers($startDate = null)
    {
        $batchArray = [];

        $today = $startDate ? Carbon::parse($startDate) : now()->startOfWeek();
        $endDate = $today->copy()->addDays(90);

        $currentDate = $today;

        while ($currentDate->lessThan($endDate)) {
            $startOfWeek = $currentDate->copy()->startOfWeek();
            $endOfWeek = $currentDate->copy()->endOfWeek();

            $year = $startOfWeek->year;
            $month = $startOfWeek->month;
            $weekOfYear = $startOfWeek->weekOfYear;

            if ($weekOfYear === 1 && $startOfWeek->month === 12) {
                $year = $year + 1;
                $month = 1;
            }

            $batchName = 'W'.$weekOfYear.'-'.$year;

            $batchData = [
                'name' => $batchName,
                'startDate' => $startOfWeek->toDateString(),
                'endDate' => $endOfWeek->toDateString(),
                'month' => $month,
                'year' => $year,
            ];

            $existingBatch = RenewalBatch::nonMotor()->where('start_date', $batchData['startDate'])->first();

            if (! $existingBatch) {
                $batchArray[] = $batchData;
                $this->info("Going to Create: {$batchName}");
            } else {
                $this->info("Batch already exists: {$batchName}");
            }

            $currentDate->addWeek();

        }

        return $batchArray;
    }

    protected function startProcessing($date)
    {
        try {
            $this->logTodayDate();

            $lastBatch = RenewalBatch::nonMotor()->orderBy('id', 'desc')->first();
            info('last batch : '.json_encode($lastBatch));
            if ($lastBatch == null) {
                $this->processBatchesFromScratch($date);
            } elseif (! $this->isBatchCurrent($lastBatch)) {
                $this->processBatchesFromLastEndDate();
            } else {
                info('batches are update to date');

                return true;
            }
        } catch (Exception $e) {
            info('Add Batch Number Failed');
            info('message: '.$e->getMessage());
        }
    }
}
