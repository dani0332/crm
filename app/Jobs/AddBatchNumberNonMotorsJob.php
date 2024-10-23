<?php

namespace App\Jobs;

use App\Console\Commands\Common\Batchable;
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
        $this->startProcessing('2024-09-01');
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
