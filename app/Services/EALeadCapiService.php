<?php

namespace App\Services;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Http\Requests\EALeadCreateRequest;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;

class EALeadCapiService
{
    public function createLead(EALeadCreateRequest $request, int $quoteTypeId, bool $isCollaborate): mixed
    {
        $endpoint = $this->resolveEndpoint($quoteTypeId);

        // ── [4a] Endpoint resolution ────────────────────────────────────────────
        LoggerService::info(self::class.' [4a] Resolved CAPI endpoint', extra: [
            'quote_type_id' => $quoteTypeId,
            'ea_model' => $request->ea_model,
            'is_collaborate' => $isCollaborate,
            'endpoint' => $endpoint ?? 'NONE — no endpoint resolved for this LOB',
        ]);

        if (! $endpoint) {
            LoggerService::warning(self::class.' [4a] No CAPI endpoint mapped for quote_type_id '.$quoteTypeId.' — returning empty response');

            return (object) [];
        }

        // ── [4b] Payload build ──────────────────────────────────────────────────
        $payload = $this->buildPayload($request, $quoteTypeId, $isCollaborate);

        // TODO: [PII] Remove 'payload' (contains email, mobile, name) from this log after 2 weeks in production.
        LoggerService::info(self::class.' [4b] CAPI request payload', extra: [
            'endpoint' => $endpoint,
            'quote_type_id' => $quoteTypeId,
            'ea_model' => $request->ea_model,
            'payload' => $payload,
        ]);

        // ── [4c] CAPI call ──────────────────────────────────────────────────────
        LoggerService::info(self::class.' [4c] Calling CAPI', extra: [
            'endpoint' => $endpoint,
        ]);

        $response = CapiRequestService::sendCAPIRequest($endpoint, $payload);

        // ── [4d] Raw CAPI response ──────────────────────────────────────────────
        $responseArray = is_object($response) ? (array) $response : (array) $response;
        // TODO: [PII] Remove 'full_response' (may contain PII from CAPI) from this log after 2 weeks in production.
        LoggerService::info(self::class.' [4d] CAPI raw response', extra: [
            'endpoint' => $endpoint,
            'quote_type_id' => $quoteTypeId,
            'response_keys' => array_keys($responseArray),
            'has_quoteUID' => isset($response->quoteUID),
            'has_uuid' => isset($response->uuid),
            'quoteUID' => $response->quoteUID ?? null,
            'uuid' => $response->uuid ?? null,
            'status' => $response->status ?? null,
            'code' => $response->code ?? null,
            'message' => $response->message ?? null,
            'full_response' => $responseArray,
        ]);

        // ── [4e] Device response normalisation ──────────────────────────────────
        // Device CAPI returns `uuid` instead of `quoteUID` — normalise for the controller
        if ($quoteTypeId === QuoteTypeId::Device && isset($response->uuid) && ! isset($response->quoteUID)) {
            LoggerService::info(self::class.' [4e] Normalising Device response: uuid -> quoteUID', extra: [
                'uuid' => $response->uuid,
            ]);
            $response->quoteUID = $response->uuid;
        }

        // ── [4f] CAPI silent-creation fallback ──────────────────────────────────
        // Some CAPI endpoints (e.g. Device) return a non-standard error response
        // ("API failed") even when they actually created the lead. If quoteUID is
        // still missing, query IMCRM directly for a lead just created with this
        // email + source=EA_IMCRM within the last 2 minutes.
        //
        // Device/Cyber/Yacht/Pet/Cycle/Jetski store email on `personal_quotes`
        // (the parent), not on their own child table, so we must query PersonalQuote
        // filtered by quote_type_id for those LOBs.
        if (! isset($response->quoteUID)) {
            $fallback = $this->findRecentlyCreatedLead($request->email, $quoteTypeId);

            if ($fallback) {
                // TODO: [PII] Remove email and original_capi_response from this log after 2 weeks in production.
                LoggerService::info(self::class.' [4f] CAPI silent-creation detected — recovered quoteUID from IMCRM fallback lookup', extra: [
                    'quote_type_id' => $quoteTypeId,
                    'email' => $request->email,
                    'recovered_uuid' => $fallback->uuid,
                    'recovered_code' => $fallback->code ?? null,
                    'original_capi_response' => $responseArray,
                ]);
                $response = (object) ['quoteUID' => $fallback->uuid];
            } else {
                // TODO: [PII] Remove email from this log after 2 weeks in production.
                LoggerService::warning(self::class.' [4f] CAPI silent-creation fallback found nothing in IMCRM', extra: [
                    'quote_type_id' => $quoteTypeId,
                    'email' => $request->email,
                    'lookup_window_minutes' => 2,
                ]);
            }
        }

        return $response;
    }

    private function findRecentlyCreatedLead(string $email, int $quoteTypeId): mixed
    {
        $since = Carbon::now()->subMinutes(2);
        $quoteTypeEnum = QuoteTypes::getName($quoteTypeId);

        if (checkPersonalQuotes($quoteTypeEnum?->value)) {
            return PersonalQuote::query()
                ->where('email', $email)
                ->where('quote_type_id', $quoteTypeId)
                ->where('source', LeadSourceEnum::EA_IMCRM)
                ->where('created_at', '>=', $since)
                ->latest('created_at')
                ->first();
        }

        $quoteModelClass = $quoteTypeEnum?->modelClass();

        if (! $quoteModelClass) {
            return null;
        }

        return $quoteModelClass::query()
            ->where('email', $email)
            ->where('source', LeadSourceEnum::EA_IMCRM)
            ->where('created_at', '>=', $since)
            ->latest('created_at')
            ->first();
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
            QuoteTypeId::Cyber => '/api/cyber/create',
            QuoteTypeId::Device => '/api/v1/device/create',
            QuoteTypeId::Yacht, QuoteTypeId::Pet, QuoteTypeId::Cycle, QuoteTypeId::Jetski => '/api/v1-save-personal-quote',
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

        if ($quoteTypeId === QuoteTypeId::Car && ! $isCollaborate) {
            $payload['isCampaignLead'] = true;
        }

        if ($quoteTypeId === QuoteTypeId::Corpline && $request->business_type_of_insurance_id) {
            $payload['businessTypeOfInsuranceId'] = $request->business_type_of_insurance_id;
        }

        if ($quoteTypeId === QuoteTypeId::GroupMedical) {
            $payload['businessTypeOfInsuranceId'] = BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;
        }

        if ($quoteTypeId === QuoteTypeId::Health && $request->health_plan_type_id) {
            $payload['healthPlanTypeId'] = $request->health_plan_type_id;
        }

        return $payload;
    }
}
