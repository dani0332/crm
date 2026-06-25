<?php

namespace App\Jobs;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class PqaAllocationBackupJob implements ShouldQueue
{
    use Queueable;

    private const BATCH_LIMIT = 50;

    public function __construct(public QuoteTypes $quoteType) {}

    public function handle(): void
    {
        LoggerService::info(self::class."::handle - PQA backup job started for {$this->quoteType->value}");

        $eligibleStatus = $this->quoteType === QuoteTypes::HEALTH
            ? QuoteStatusEnum::NewLead
            : QuoteStatusEnum::QualificationPending;

        $leads = $this->quoteType->model()::query()
            ->whereNull('pq_advisor_id')
            ->where('quote_status_id', $eligibleStatus)
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->whereNotIn('source', [
                LeadSourceEnum::IMCRM,
                LeadSourceEnum::RENEWAL_UPLOAD,
                LeadSourceEnum::EA_IMCRM,
                LeadSourceEnum::REVIVAL,
                LeadSourceEnum::REVIVAL_REPLIED,
                LeadSourceEnum::REVIVAL_PAID,
                LeadSourceEnum::REVIVAL_SHORT,
                LeadSourceEnum::REVIVAL_ANNUAL,
            ])
            ->limit(self::BATCH_LIMIT)
            ->get();

        foreach ($leads as $lead) {
            DispatchPqaAllocationJob::dispatch($lead->uuid, $this->quoteType);
        }

        LoggerService::info(self::class."::handle - PQA backup job dispatched {$leads->count()} allocations for {$this->quoteType->value}");
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->quoteType->value))->dontRelease()];
    }
}
