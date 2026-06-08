<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Http\Requests\EALeadCreateRequest;

class EALeadCapiService
{
    public function createLead(EALeadCreateRequest $request, int $quoteTypeId, bool $isCollaborate): mixed
    {
        $endpoint = $this->resolveEndpoint($quoteTypeId);

        if (! $endpoint) {
            return (object) [];
        }

        return CapiRequestService::sendCAPIRequest($endpoint, $this->buildPayload($request, $quoteTypeId, $isCollaborate));
    }

    private function resolveEndpoint(int $quoteTypeId): ?string
    {
        return match ($quoteTypeId) {
            QuoteTypeId::Car => '/api/v1-save-car-quote',
            QuoteTypeId::Bike => '/api/v1-save-bike-quote',
            QuoteTypeId::Travel => '/api/v1-save-travel-quote',
            QuoteTypeId::Health => '/api/v1-save-health-quote',
            QuoteTypeId::Life => '/api/v2-save-life-quote',
            QuoteTypeId::Home => '/api/v2-save-home-quote',
            QuoteTypeId::Business, QuoteTypeId::Corpline, QuoteTypeId::GroupMedical => '/api/v1-save-business-quote',
            QuoteTypeId::Savings => '/api/v1-save-savings-quote',
            default => $this->resolveDefaultV1Endpoint($quoteTypeId),
        };
    }

    private function resolveDefaultV1Endpoint(int $quoteTypeId): ?string
    {
        $quoteTypeEnum = QuoteTypes::getName($quoteTypeId);

        return $quoteTypeEnum ? '/api/v1-save-'.strtolower($quoteTypeEnum->value).'-quote' : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(EALeadCreateRequest $request, int $quoteTypeId, bool $isCollaborate): array
    {
        $payload = [
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'email' => $request->email,
            'mobileNo' => $request->mobile_no,
            'source' => LeadSourceEnum::EA_IMCRM,
            'referenceUrl' => config('constants.APP_URL'),
            'quoteTypeId' => $quoteTypeId,
            'eaModel' => $request->ea_model,
            'leadGeneratorId' => auth()->id(),
            'createdById' => auth()->id(),
            'quoteStatusId' => QuoteStatusEnum::NewLead,
        ];

        if ($isCollaborate) {
            $payload['advisorId'] = auth()->id();
        }

        if ($quoteTypeId === QuoteTypeId::Corpline && $request->business_type_of_insurance_id) {
            $payload['businessTypeOfInsuranceId'] = $request->business_type_of_insurance_id;
        }

        if ($quoteTypeId === QuoteTypeId::Health && $request->health_plan_type_id) {
            $payload['healthPlanTypeId'] = $request->health_plan_type_id;
        }

        return $payload;
    }
}
