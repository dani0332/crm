<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Services\ClaimAllocation\ClaimAllocationService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ClaimReassignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 2;
    public $timeout = 15;
    public $backoff = 30;
    private $managerId;
    /**
     * Create a new job instance.
     */
    public function __construct($managerId)
    {
        $this->managerId = $managerId;
    }

    /**
     * Execute the job.
     */
    private function shouldProceed(): bool
    {
        $start_time = Carbon::createFromFormat('H:i', getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_START_TIME));
        $end_time = Carbon::createFromFormat('H:i', getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_END_TIME));

        return now()->between($start_time, $end_time) && ((int) config('constants.QUOTE_ALLOCATION_MASTER_SWITCH') == 1);
    }
    public function handle()
    {
        LoggerService::info(self::class.'::handle - Reassignment job started at : '.now());
        if (! $this->shouldProceed() && ! now()->isWeekend()) {
            LoggerService::info('Reassignment job is not proceeding as per business timings');

            return false;
        }

        $leads = app(ClaimAllocationService::class)->fetchReAssignmentLeads($this->managerId);

        if ($leads->count() === 0) {
            LoggerService::info(self::class.'::handle - No  lead found or either lead is not under assignment criteria');
            LoggerService::info(self::class.'::handle - Reassignment job ended at : '.now());

            return false; // when lead is not on criteria or not found
        }

        foreach ($leads as $lead) {
            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::CLAIM_ALLOCATION);

            LoggerService::info(self::class.'::handle - Reassignment started ---------');

            app(ClaimAllocationService::class)->execute(
                claimUuid: $lead->uuid,
                quoteTypeId: $lead->quote_type_id,
                isReassignmentJob: true
            );

            info(self::class.'::handle - Reassignment ended ---------');
        }

        LoggerService::endLogging();
        LoggerService::info(self::class.'::handle - Reassignment job ended at ');
    }

    public function middleware()
    {
        if ($this->managerId) {
            return [(new WithoutOverlapping("{$this->managerId}"))->dontRelease()];
        }

        return [];
    }
}
