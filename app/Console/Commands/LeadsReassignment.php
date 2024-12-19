<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Jobs\ReAssignBikeLeadsJob;
use App\Jobs\ReAssignCarLeadsJob;
use App\Jobs\ReAssignHealthLeadsJob;
use App\Jobs\ReAssignLeads;
use App\Services\ApplicationStorageService;
use App\Services\BikeAllocationService;
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
        $enableLeadReassignment = $applicationStorageService->getValueByKey(ApplicationStorageEnums::ENABLE_LEAD_REASSIGNMENT);
        $shouldProceed = now()->between($start_time, $end_time) && ((int) config('constants.CAR_LEAD_ALLOCATION_MASTER_SWITCH') == 1) && ($enableLeadReassignment == 1);
        info('shouldProceed for lead reassignment started at : '.$currentIteration.' and value is : '.$shouldProceed);

        $isHoliday = $this->isHoliday();

        if ($shouldProceed && ! now()->isWeekend() && ! $isHoliday) {
            dispatch(new ReAssignCarLeadsJob(app(CarAllocationService::class), 0));
            info('Car lead reassignment job  for '.$currentIteration.' is dispatched');

            dispatch(new ReAssignHealthLeadsJob(app(HealthAllocationService::class), 0));
            info('Health lead reassignment job  for '.$currentIteration.' is dispatched');

            dispatch(new ReAssignBikeLeadsJob(app(BikeAllocationService::class), 0));
            info('Bike lead reassignment job  for '.$currentIteration.' is dispatched');

            // Disabled Leads Auto Re Assignment for below Types as this is not needed at the moment
            // foreach ([QuoteTypes::CORPLINE, QuoteTypes::LIFE, QuoteTypes::HOME, QuoteTypes::PET, QuoteTypes::YACHT, QuoteTypes::CYCLE] as $quoteType) {
            //     ReAssignLeads::dispatch($quoteType);
            //     info("{$quoteType->value} lead reassignment job  for {$currentIteration} is dispatched");
            // }

            info('------------------- Lead reassignment Command Finished for '.$currentIteration.' -------------------');
        } else {
            info('Lead reassignment time is off');
            info('------------------- Lead reassignment Command Finished for '.$currentIteration.' -------------------');

            return;
        }
    }

    private function isHoliday()
    {
        // Eid Holidays For 2024
        $exclusionDates = [
            '2024-06-15',
            '2024-06-16',
            '2024-06-17',
            '2024-06-18',
        ];

        $today = now()->format('Y-m-d');

        return in_array($today, $exclusionDates);
    }
}
