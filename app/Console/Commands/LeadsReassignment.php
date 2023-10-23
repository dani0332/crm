<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Jobs\ReAssignCarLeadsJob;
use App\Jobs\ReAssignHealthLeadsJob;
use App\Services\ApplicationStorageService;
use App\Services\CarAllocationService;
use App\Services\HealthAllocationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LeadsReassignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'LeadsReassignment:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lead Re Assignment cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ApplicationStorageService $applicationStorageService)
    {
        $currentIteration = now();

        info('------------------- Lead Reassignment Command Started At : '.$currentIteration.' -------------------');

        $start_time = Carbon::createFromFormat('H:i', $applicationStorageService->getValueByKey(ApplicationStorageEnums::REASSIGNMENT_START_TIME));
        $end_time = Carbon::createFromFormat('H:i', $applicationStorageService->getValueByKey(ApplicationStorageEnums::REASSIGNMENT_END_TIME));
        $shouldProceed = now()->between($start_time, $end_time);
        if ($shouldProceed && now()->isWeekday()) {
            dispatch(new ReAssignCarLeadsJob(app(CarAllocationService::class), 0));
            dispatch(new ReAssignHealthLeadsJob(app(HealthAllocationService::class), 0));
            info('------------------- Lead reassignment Command Finished for '.$currentIteration.' -------------------');
        } else {
            info('Lead reassignment time is off');
            info('------------------- Lead reassignment Command Finished for '.$currentIteration.' -------------------');

            return;
        }
    }
}
