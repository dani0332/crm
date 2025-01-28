<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Services\AllocationService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ReAssignLeads implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public QuoteTypes $quoteType, public $advisorId = null)
    {
        //
    }

    private function shouldProceed(): bool
    {
        $start_time = Carbon::createFromFormat('H:i', getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_START_TIME));
        $end_time = Carbon::createFromFormat('H:i', getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_END_TIME));

        return now()->between($start_time, $end_time) && ((int) config('constants.QUOTE_ALLOCATION_MASTER_SWITCH') == 1);
    }

    private function fetchReAssignmentLeads()
    {
        $from = now()->subDay()->setTime(12, 30)->format(config('constants.DB_DATE_FORMAT_MATCH'));
        info(self::class."::fetchReAssignmentLeads - leads will be picked up in reassignment from : {$from}");

        return $this->quoteType->model()::whereBetween('created_at', [$from, now()])
            ->when($this->quoteType->isPersonalQuote(), function ($q) {
                $q->where('quote_type_id', $this->quoteType->id());
            })
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->where('source', '!=', LeadSourceEnum::IMCRM)
            ->when($this->advisorId, function ($q) {
                $q->where('advisor_id', $this->advisorId);
            }, function ($q) {
                $advisors = (new AllocationService)->getUnavailableAdvisor();
                $advisorIds = $advisors->pluck('user_id');
                $q->whereIn('advisor_id', $advisorIds);
            })
            ->get();
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        info(self::class."::handle - Reassignment {$this->quoteType->value} job started at : ".now());
        if (! $this->shouldProceed() && ! now()->isWeekend()) {
            info('Reassignment job is not proceeding as per business timings');

            return false;
        }

        $leads = $this->fetchReAssignmentLeads();

        if ($leads->count() === 0) {
            info(self::class."::handle - No {$this->quoteType->value} lead found or either lead is not under assignment criteria");
            info(self::class."::handle - Reassignment {$this->quoteType->value} job ended at : ".now());

            return false; // when lead is not on criteria or not found
        }

        foreach ($leads as $lead) {
            info(self::class."::handle - Reassignment of lead : {$lead->uuid} started ---------");

            $this->quoteType->allocate(
                uuid: $lead->uuid,
                overrideAdvisorId: true,
                isReAssignment: true
            );

            info(self::class."::handle - Reassignment of lead : {$lead->uuid} ended ---------");
        }

        info(self::class."::handle - Reassignment {$this->quoteType->value} job ended at : ".now());
    }

    public function middleware()
    {
        if ($this->advisorId) {
            return [(new WithoutOverlapping("{$this->quoteType->value}_{$this->advisorId}"))->dontRelease()];
        }

        return [];
    }
}
