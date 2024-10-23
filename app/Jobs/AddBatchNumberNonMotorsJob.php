<?php

namespace App\Jobs;

use App\Console\Commands\Common\Batchable;
use App\Models\RenewalBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class AddBatchNumberNonMotorsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 1;
    public $timeout = 30;
    public $backoff = 10;
    private $renewalBatchId = 'renewal_batch_number';

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
            $this->logTodayDate();

            $lastBatch = RenewalBatch::whereNull('quote_type_id')->orderBy('id', 'desc')->first();
            info('last batch : '.json_encode($lastBatch));
            if ($lastBatch == null) {
                $this->processBatchesFromScratch('2024-09-01');
            } elseif (! $this->isBatchCurrent($lastBatch)) {
                $this->processBatchesFromLastEndDate($lastBatch);
            } else {
                info('batches are update to date');

                return true;
            }
        } catch (\Exception $e) {
            info('Add Batch Number Failed');
            info('message: '.$e->getMessage());
        }
    }

    protected function generateBatchNumbers($startDate)
    {
        $batchArray = [];

        while ($startDate < now()) {
            $currentDate = $startDate->copy();
            $nextWeek = $startDate->copy()->addDays(6);

            $month = $currentDate->format('M');
            $year = $currentDate->format('y');
            $monthNumber = $currentDate->format('m');
            $fullYear = $currentDate->format('Y');

            $batchArray[] = [
                'name' => $month.'-'.$year,
                'startDate' => $currentDate->toDateString(),
                'endDate' => $nextWeek->toDateString(),
                'month' => $monthNumber,
                'year' => $fullYear,
            ];

            $startDate->addWeek();
        }

        return $batchArray;
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalBatchId))->dontRelease()];
    }
}
