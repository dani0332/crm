<?php

namespace App\Jobs;

use App\Models\QuoteBatches;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class AddBatchNumberJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 1;
    public $timeout = 30;
    public $backoff = 10;
    private $leadAllocationBatchId = 'lead_allocation_batch_number';

    /**
     * Create a new job instance.
     *
     * @return void
     */

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            info('today date for batch job is : '.json_encode(now()->toDateString()));
            $lastBatch = QuoteBatches::orderBy('id', 'desc')->first();
            info('last batch : '.json_encode($lastBatch));
            if ($lastBatch == null) {
                info('inside creating batches from scratch');
                $batches = $this->generateBatchNumbers(Carbon::parse('2018-08-06'));
                $this->createBatches($batches);
            } elseif (! (now()->startOfDay() >= Carbon::parse($lastBatch->start_date)->startOfDay() && now()->endOfDay() <= Carbon::parse($lastBatch->end_date)->endOfDay())) {
                info('inside creating batch of current week');
                $batches = $this->generateBatchNumbers(Carbon::parse($lastBatch->end_date)->addDays(1));
                $this->createBatches($batches);
            } else {
                info('batches are update to date');

                return true;
            }
        } catch (\Exception $e) {
            info('Add Batch Number Job Failed');
            info('message: '.$e->getMessage());
        }
    }

    private function createBatches($batches)
    {
        if (count($batches) > 0) {
                    foreach ($batches as $batch) {
                        $this->insertQuoteBatch($batch);
                    }
                    info('batches created');
                }
    }

    private function insertQuoteBatch($batch)
    {
        QuoteBatches::insert([
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
        $count = QuoteBatches::all()->count() + 1;
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

    public function middleware()
    {
        return [(new WithoutOverlapping($this->leadAllocationBatchId))->dontRelease()];
    }
}
