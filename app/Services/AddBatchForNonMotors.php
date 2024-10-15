<?php

namespace App\Services;

use App\Models\RenewalBatch;
use Carbon\Carbon;

class AddBatchForNonMotors extends BaseService
{
    public function handle()
    {
        try {
            info('today date for batch job is : '.json_encode(now()->toDateString()));
            $lastBatch = RenewalBatch::whereNull('quote_type_id')->orderBy('id', 'desc')->first();
            info('last batch : '.json_encode($lastBatch));
            if ($lastBatch == null) {
                info('inside creating batches from scratch');
                $batches = $this->generateBatchNumbers(Carbon::parse('2024-07-29'));
                if (count($batches) > 0) {
                    foreach ($batches as $batch) {
                        $this->insertQuoteBatch($batch);
                    }
                    info('batches created');
                }
            } elseif (! (now()->startOfDay() >= Carbon::parse($lastBatch->start_date)->startOfDay() && now()->endOfDay() <= Carbon::parse($lastBatch->end_date)->endOfDay())) {
                info('inside creating batch of current week');
                $batches = $this->generateBatchNumbers(Carbon::parse($lastBatch->end_date)->addDays(1));
                if (count($batches) > 0) {
                    foreach ($batches as $batch) {
                        $this->insertQuoteBatch($batch);
                    }
                    info('batches created');
                }
            } else {
                info('batches are update to date');

                return true;
            }
        } catch (\Exception $e) {
            info('Add Batch Number Failed');
            info('message: '.$e->getMessage());
        }
    }

    private function insertQuoteBatch($batch)
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

    private function generateBatchNumbers($startDate)
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
