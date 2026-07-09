<?php

namespace App\Jobs;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
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
            ? [QuoteStatusEnum::NewLead]
            : [QuoteStatusEnum::QualificationPending, QuoteStatusEnum::NewLead];

        $leads = $this->quoteType->model()::query()
            ->whereNull('pq_advisor_id')
            ->whereIn('quote_status_id', $eligibleStatus)
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
            ->whereDate('created_at', '>', '2026-07-01')
            ->when($this->quoteType === QuoteTypes::HEALTH, function ($query) {
                $query->where('created_at', '<=', Carbon::now()->subMinutes(10));
            })
            ->when($this->quoteType === QuoteTypes::GROUP_MEDICAL, function ($query) {
                $query->where('created_at', '<=', Carbon::now()->subMinutes(15));
            })
            ->when($this->quoteType === QuoteTypes::GROUP_MEDICAL, function($q) {
                $q->where('business_type_of_insurance_id', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL);
            })
            ->when($this->quoteType === QuoteTypes::CORPLINE, function($q) {
                $q->where('business_type_of_insurance_id', '!=', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL);
            })
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

    public function failed(\Throwable $e): void
    {
        LoggerService::error(self::class.'::failed - PQA backup allocation job exhausted retries', [
            'quote_type' => $this->quoteType->value,
        ], $e);
    }
}
