<?php

namespace App\Services;

use App\Models\QuoteBatches;
use App\Models\RenewalBatch;
use Carbon\Carbon;

class AddRenewalBatchNumberService
{

    public function handle()
    {
        try {
            info('today date for batch job is : '.json_encode(now()->toDateString()));
            $lastBatch = RenewalBatch::orderBy('id', 'desc')->first();
            info('last batch : '.json_encode($lastBatch));
            if ($lastBatch == null) {
                info('inside creating batches from scratch');
                $batches = $this->generateBatchNumbers(Carbon::parse('2018-08-06'));
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
            'name' => explode('|', $batch)[1],
            'start_date' => explode(',', $batch)[0],
            'end_date' => explode('|', explode(',', $batch)[1])[0],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function generateBatchNumbers($startDate)
    {
        $batchArray = [];
        $count = RenewalBatch::all()->count() + 1;
        while ($startDate < now()) {
            $currentDate = $startDate->toDateString();
            $nextWeek = $startDate->addDays(6)->toDateString();
            $key = $currentDate.','.$nextWeek;
            $value = 'Batch '.$count;
            array_push($batchArray, $key.'|'.$value);
            $count++;
        }

        return $batchArray;
    }
}