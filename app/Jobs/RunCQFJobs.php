<?php

namespace App\Jobs;

use App\Services\CQF\CarCQFRenewalExecutionService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunCQFJobs implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected ?Carbon $startDate = null) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::info(self::class.': Running CQF jobs');

        app(CarCQFRenewalExecutionService::class)->processCarCQFRenewalLeads($this->startDate);

        LoggerService::info(self::class.': CQF jobs have been completed');
    }
}
