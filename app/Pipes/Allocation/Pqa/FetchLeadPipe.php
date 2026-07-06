<?php

declare(strict_types=1);

namespace App\Pipes\Allocation\Pqa;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FetchLeadPipe extends BasePqaAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request, true);

        $quoteUuid = $this->allocationRequest->getQuoteUUID();
        $refId = $this->allocationRequest->getRefID();

        $lead = $this->allocationRequest->model()
            ->where(fn ($query) => $query->where('uuid', $quoteUuid)->orWhere('code', $refId))
            ->whereNotIn('quote_status_id', [
                QuoteStatusEnum::Fake,
                QuoteStatusEnum::Duplicate,
                QuoteStatusEnum::Lost,
            ])
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
            ->when(
                ! $this->allocationRequest->isOverrideAdvisorRequest(),
                fn ($query) => $query->whereNull('pq_advisor_id'),
            )
            ->first();

        if (! $lead) {
            LoggerService::info(self::class.' - lead not found or not eligible for PQA allocation');
            $this->throw('Lead not found or not eligible for Pre Qualification Advisor allocation', self::NOT_FOUND);
        }

        $this->lead = $lead;
        $this->allocationRequest->setLead($lead);

        LoggerService::info(self::class.' - lead loaded for PQA allocation', extra: [
            'leadUuid' => $lead->uuid,
            'leadId' => $lead->id,
        ]);

        return $next($request);
    }
}
