<?php

namespace App\Pipelines\Allocation\Common;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteStatusEnum;
use App\Exceptions\Allocation\AllocationException;
use App\Services\Logger\LoggerService;
use App\Strategies\Allocations\PipelineHandlers\AllocationRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;

abstract class BaseAllocationPipeline
{
    public const NOT_FOUND = Response::HTTP_NOT_FOUND;
    public const OK = Response::HTTP_OK;

    protected AllocationRequest $allocationRequest;
    protected ?Model $lead = null;

    protected function setRequest(AllocationRequest $allocationRequest, bool $startLogging = true)
    {
        $this->allocationRequest = $allocationRequest;

        if ($startLogging) {
            $this->startQuoteLogging();
        }

        if ($this->allocationRequest->get('lead')) {
            $this->setLead($this->allocationRequest->get('lead'));
        }
    }

    protected function startQuoteLogging()
    {
        LoggerService::startQuoteLogging(
            $this->allocationRequest->getRefID(),
            LoggerFeatureEnum::ALLOCATION
        );
    }

    protected function setLead(Model $lead)
    {
        $this->lead = $lead;
    }

    protected function logLeadData(Model $lead)
    {
        LoggerService::info(self::class.'::logLeadData', [
            'payment_status_id' => $lead->payment_status_id,
            'sic_advisor_requested' => $lead->sic_advisor_requested,
            'quote_status_id' => $lead->quote_status_id,
            'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
            'sic_flow_enabled' => $lead->sic_flow_enabled,
            'parent_quote_id' => $lead->parent_id,
            'source' => $lead->source,
        ]);
    }

    protected function getBaseLead(): ?Model
    {
        $lead = $this->allocationRequest->model()->where('uuid', $this->allocationRequest->getQuoteUUID())->first();

        if (! $lead) {
            LoggerService::info('Lead not found');

            return null;
        }

        $this->logLeadData($lead);

        return $lead;
    }

    protected function getLeadBaseQuery()
    {
        return $this->allocationRequest->model()
            ->where('uuid', $this->allocationRequest->getQuoteUUID())
            ->whereNotIn('quote_status_id', [
                QuoteStatusEnum::Fake,
                QuoteStatusEnum::Duplicate,
                QuoteStatusEnum::Lost,
            ])
            ->when(! $this->allocationRequest->getOverrideAdvisorId(), fn ($q) => $q->whereNull('advisor_id'));
    }

    protected function throw(string $message, int $code = 500)
    {
        throw new AllocationException($message, $code);
    }
}
