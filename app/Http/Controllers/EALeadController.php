<?php

namespace App\Http\Controllers;

use App\Enums\QuoteTypes;
use App\Http\Requests\EALeadCreateRequest;
use App\Jobs\SendEALeadSubmittedEmailJob;
use App\Services\EALeadCapiService;
use App\Services\EALeadDuplicateService;
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

        // Block if an active renewal-upload lead still covers this client
        if ($this->duplicateService->isBlockedByRenewalExpiry($email, $mobileNo, $quoteTypeId)) {
            return response()->json([
                'duplicate' => true,
                'message' => 'A renewal-upload lead exists for this client and has not yet expired.',
            ], 422);
        }

        // Duplicate detection within 60-day window
        $existing = $this->duplicateService->findDuplicate($email, $mobileNo, $quoteTypeId);
        if ($existing) {
            return response()->json([
                'duplicate' => true,
                'existing_advisor' => optional($existing->advisor)->name,
                'message' => 'A lead already exists for this client within the past 60 days.',
            ], 422);
        }

        $isCollaborate = $request->ea_model === 'collaborate';

        try {
            $capiResponse = $this->eaLeadCapiService->createLead($request, $quoteTypeId, $isCollaborate);
        } catch (RequestException $e) {
            $body = (string) $e->getResponse()?->getBody();
            $decoded = json_decode($body, true);
            $capiMessage = $decoded['message'] ?? null;

            $userMessage = $this->resolveCapiErrorMessage($capiMessage);

            return response()->json(['success' => false, 'message' => $userMessage], 422);
        }

        if (! isset($capiResponse->quoteUID)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to create EA lead via CAPI.',
            ], 422);
        }

        $quoteTypeEnum = QuoteTypes::getName($quoteTypeId);
        $quoteModelClass = $quoteTypeEnum?->modelClass();
        $quote = $quoteModelClass ? $quoteModelClass::query()->where('uuid', $capiResponse->quoteUID)->first() : null;
        if (! $quote) {
            return response()->json([
                'success' => false,
                'message' => 'EA lead was created in CAPI but could not be loaded in IMCRM.',
            ], 422);
        }

        // Dispatch ILA: collaborate leads use a modified allocation that respects the
        // assigned-expert-advisor permission (see EACollaborateILAJob / modified pipe).
        if ($quoteTypeEnum) {
            dispatch(fn () => $quoteTypeEnum->allocate($capiResponse->quoteUID));
        }

        $quoteTypeName = strtolower($quoteTypeEnum?->value ?? (string) $quoteTypeId);
        SendEALeadSubmittedEmailJob::dispatch($quote, $quoteTypeName);

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
