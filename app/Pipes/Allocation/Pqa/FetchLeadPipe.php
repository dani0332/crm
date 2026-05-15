<?php

declare(strict_types=1);

namespace App\Pipes\Allocation\Pqa;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\BusinessQuote;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FetchLeadPipe extends BasePqaAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request, true);

        $lead = BusinessQuote::query()
            ->with('quoteDetail')
            ->where('uuid', $this->allocationRequest->getQuoteUUID())
            ->where('business_type_of_insurance_id', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL)
            ->whereNotIn('quote_status_id', [
                QuoteStatusEnum::Fake,
                QuoteStatusEnum::Duplicate,
                QuoteStatusEnum::Lost,
            ])
            ->when(
                ! $this->allocationRequest->isOverrideAdvisorRequest(),
                fn ($query) => $query->whereNull('pq_advisor_id'),
            )
            ->first();

        if (! $lead) {
            LoggerService::info(self::class.' - Group Medical lead not found or not eligible for PQA allocation');
            $this->throw('Lead not found or not eligible for Pre Qualification Advisor allocation', self::NOT_FOUND);
        }

        $this->lead = $lead;
        $this->allocationRequest->setLead($lead);

        LoggerService::info(self::class.' - Group Medical lead loaded for PQA allocation', extra: [
            'leadUuid' => $lead->uuid,
            'leadId' => $lead->id,
        ]);

        return $next($request);
    }
}
