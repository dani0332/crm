<?php

namespace App\Http\Controllers;

use App\Enums\QuoteTypes;
use App\Http\Requests\EALeadCreateRequest;
use App\Jobs\SendEALeadSubmittedEmailJob;
use App\Models\PersonalQuote;
use App\Services\EALeadCapiService;
use App\Services\EALeadDuplicateService;
use App\Services\Logger\LoggerService;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;

class EALeadController extends Controller
{
    public function __construct(
        private readonly EALeadDuplicateService $duplicateService,
        private readonly EALeadCapiService $eaLeadCapiService,
    ) {}

    public function store(EALeadCreateRequest $request): JsonResponse
    {
        $quoteTypeId = (int) $request->quote_type_id;
        $email = $request->email;
        $mobileNo = $request->mobile_no;

        // ── [1] Request received ────────────────────────────────────────────────
        // TODO: [PII] Remove email, mobile_no, first_name, last_name from this log after 2 weeks in production.
        LoggerService::info(self::class.' [1/7] EA lead creation request received', extra: [
            'quote_type_id' => $quoteTypeId,
            'ea_model' => $request->ea_model,
            'email' => $email,
            'mobile_no' => $mobileNo,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);

        // ── [2] Renewal expiry duplicate check ──────────────────────────────────
        // TODO: [PII] Remove email, mobile_no from this log after 2 weeks in production.
        LoggerService::info(self::class.' [2/7] Checking renewal expiry duplicate', extra: [
            'email' => $email,
            'mobile_no' => $mobileNo,
            'quote_type_id' => $quoteTypeId,
        ]);

        if ($this->duplicateService->isBlockedByRenewalExpiry($email, $mobileNo, $quoteTypeId)) {
            LoggerService::warning(self::class.' [2/7] BLOCKED — active renewal upload lead exists', extra: [
                'email' => $email,
                'quote_type_id' => $quoteTypeId,
            ]);

            return response()->json([
                'duplicate' => true,
                'message' => 'A renewal-upload lead exists for this client and has not yet expired.',
            ], 422);
        }

        // ── [3] 60-day duplicate check ──────────────────────────────────────────
        // TODO: [PII] Remove email, mobile_no from this log after 2 weeks in production.
        LoggerService::info(self::class.' [3/7] Checking 60-day duplicate', extra: [
            'email' => $email,
            'mobile_no' => $mobileNo,
            'quote_type_id' => $quoteTypeId,
        ]);

        $existing = $this->duplicateService->findDuplicate($email, $mobileNo, $quoteTypeId);
        if ($existing) {
            LoggerService::warning(self::class.' [3/7] BLOCKED — duplicate lead found within 60 days', extra: [
                'email' => $email,
                'quote_type_id' => $quoteTypeId,
                'existing_uuid' => $existing->uuid ?? null,
                'existing_code' => $existing->code ?? null,
                'existing_advisor' => optional($existing->advisor)->name,
                'existing_source' => $existing->source ?? null,
            ]);

            return response()->json([
                'duplicate' => true,
                'existing_advisor' => optional($existing->advisor)->name,
                'message' => 'A lead already exists for this client within the past 60 days.',
            ], 422);
        }

        LoggerService::info(self::class.' [3/7] No duplicate found — proceeding', extra: [
            'email' => $email,
            'quote_type_id' => $quoteTypeId,
        ]);

        // ── [4] CAPI request ────────────────────────────────────────────────────
        $isCollaborate = $request->ea_model === 'collaborate';

        LoggerService::info(self::class.' [4/7] Sending lead to CAPI', extra: [
            'quote_type_id' => $quoteTypeId,
            'ea_model' => $request->ea_model,
            'is_collaborate' => $isCollaborate,
        ]);

        try {
            $capiResponse = $this->eaLeadCapiService->createLead($request, $quoteTypeId, $isCollaborate);
        } catch (RequestException $e) {
            $body = (string) $e->getResponse()?->getBody();
            $decoded = json_decode($body, true);
            $capiMessage = $decoded['message'] ?? null;

            // TODO: [PII] Remove email and response_body (may contain PII) from this log after 2 weeks in production.
            LoggerService::error(self::class.' [4/7] CAPI HTTP error (RequestException)', extra: [
                'quote_type_id' => $quoteTypeId,
                'ea_model' => $request->ea_model,
                'email' => $email,
                'http_status' => $e->getResponse()?->getStatusCode(),
                'capi_message' => $capiMessage,
                'response_body' => $body,
            ]);

            return response()->json(['success' => false, 'message' => $this->resolveCapiErrorMessage($capiMessage)], 422);
        }

        // ── [5] Validate CAPI response ──────────────────────────────────────────
        // TODO: [PII] Remove email from this log after 2 weeks in production.
        LoggerService::info(self::class.' [5/7] Validating CAPI response for quoteUID', extra: [
            'quote_type_id' => $quoteTypeId,
            'email' => $email,
            'has_quoteUID' => isset($capiResponse->quoteUID),
            'quoteUID' => $capiResponse->quoteUID ?? null,
        ]);

        if (! isset($capiResponse->quoteUID)) {
            LoggerService::error(self::class.' [5/7] FAILED — CAPI response has no quoteUID', extra: [
                'quote_type_id' => $quoteTypeId,
                'ea_model' => $request->ea_model,
                'email' => $email,
                'capi_response' => (array) $capiResponse,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create EA lead via CAPI.',
            ], 422);
        }

        // ── [6] Load quote from IMCRM ───────────────────────────────────────────
        $quoteTypeEnum = QuoteTypes::getName($quoteTypeId);
        $quoteModelClass = $quoteTypeEnum?->modelClass();

        LoggerService::info(self::class.' [6/7] Loading quote from IMCRM', extra: [
            'quote_type_id' => $quoteTypeId,
            'quote_type_enum' => $quoteTypeEnum?->value,
            'quote_model_class' => $quoteModelClass,
            'capi_quote_uid' => $capiResponse->quoteUID,
        ]);

        if (checkPersonalQuotes($quoteTypeEnum?->value)) {
            $quote = PersonalQuote::query()->where('uuid', $capiResponse->quoteUID)->first();
        } else {
            $quote = $quoteModelClass ? $quoteModelClass::query()->where('uuid', $capiResponse->quoteUID)->first() : null;
        }
        if (! $quote) {
            // TODO: [PII] Remove email from this log after 2 weeks in production.
            LoggerService::error(self::class.' [6/7] FAILED — quote not found in IMCRM after CAPI creation', extra: [
                'quote_type_id' => $quoteTypeId,
                'quote_type_enum' => $quoteTypeEnum?->value,
                'quote_model_class' => $quoteModelClass,
                'capi_quote_uid' => $capiResponse->quoteUID,
                'email' => $email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'EA lead was created in CAPI but could not be loaded in IMCRM.',
            ], 422);
        }

        LoggerService::info(self::class.' [6/7] Quote loaded from IMCRM', extra: [
            'quote_uuid' => $quote->uuid,
            'quote_code' => $quote->code,
            'quote_type_id' => $quoteTypeId,
        ]);

        // ── [7] Dispatch allocation + email ─────────────────────────────────────
        if ($quoteTypeEnum) {
            LoggerService::info(self::class.' [7/7] Dispatching ILA allocation', extra: [
                'quote_uuid' => $quote->uuid,
                'quote_type_enum' => $quoteTypeEnum->value,
            ]);
            dispatch(fn () => $quoteTypeEnum->allocate($capiResponse->quoteUID));
        }

        $quoteTypeName = strtolower($quoteTypeEnum?->value ?? (string) $quoteTypeId);

        LoggerService::info(self::class.' [7/7] Dispatching EA lead submitted email', extra: [
            'quote_uuid' => $quote->uuid,
            'quote_code' => $quote->code,
            'quote_type_name' => $quoteTypeName,
        ]);

        SendEALeadSubmittedEmailJob::dispatch($quote, $quoteTypeName)->delay(now()->addMinutes(1));

        // TODO: [PII] Remove email from this log after 2 weeks in production.
        LoggerService::info(self::class.' [7/7] EA lead creation completed successfully', extra: [
            'quote_type_id' => $quoteTypeId,
            'ea_model' => $request->ea_model,
            'quote_uuid' => $quote->uuid,
            'quote_code' => $quote->code,
            'email' => $email,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'code' => $quote->code,
            'uuid' => $quote->uuid,
            'quote_type_id' => $quoteTypeId,
            'message' => 'EA lead created successfully.',
        ]);
    }

    private function resolveCapiErrorMessage(?string $capiMessage): string
    {
        if ($capiMessage && str_contains(strtolower($capiMessage), 'email')) {
            return 'Please enter a valid email address.';
        }

        return 'Unable to create EA lead. Please check your inputs and try again.';
    }
}
