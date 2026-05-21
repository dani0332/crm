<?php

namespace App\Http\Controllers;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\QuoteTypeShortCode;
use App\Http\Requests\EALeadCreateRequest;
use App\Jobs\SendEALeadSubmittedEmailJob;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Services\EALeadDuplicateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class EALeadController extends Controller
{
    public function __construct(private readonly EALeadDuplicateService $duplicateService) {}

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

        $uuid = Str::uuid()->toString();
        $shortCode = QuoteTypeShortCode::getName($quoteTypeId);
        $code = $shortCode.'-'.$uuid;
        $isCollaborate = $request->ea_model === 'collaborate';

        $quote = $this->createQuoteRecord($request, $uuid, $code, $quoteTypeId, $isCollaborate);

        // Dispatch ILA: collaborate leads use a modified allocation that respects the
        // assigned-expert-advisor permission (see EACollaborateILAJob / modified pipe).
        $quoteTypeEnum = QuoteTypes::getName($quoteTypeId);
        if ($quoteTypeEnum) {
            if ($isCollaborate) {
                dispatch(fn () => $quoteTypeEnum->allocate($uuid));
            } else {
                dispatch(fn () => $quoteTypeEnum->allocate($uuid));
            }
        }

        SendEALeadSubmittedEmailJob::dispatch($quote, $quoteTypeId);

        return response()->json([
            'success' => true,
            'code' => $code,
            'message' => 'EA lead created successfully.',
        ]);
    }

    private function createQuoteRecord(
        EALeadCreateRequest $request,
        string $uuid,
        string $code,
        int $quoteTypeId,
        bool $isCollaborate,
    ): CarQuote|HealthQuote|PersonalQuote {
        $baseData = [
            'uuid' => $uuid,
            'code' => $code,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'mobile_no' => $request->mobile_no,
            'source' => LeadSourceEnum::EA_IMCRM,
            'ea_model' => $request->ea_model,
            'lead_generator_id' => auth()->id(),
            'quote_status_id' => QuoteStatusEnum::NewLead,
            'created_by_id' => auth()->id(),
        ];

        // For collaborate model, the creating user IS the assigned advisor
        if ($isCollaborate) {
            $baseData['advisor_id'] = auth()->id();
        }

        if ($quoteTypeId === QuoteTypeId::Car) {
            return CarQuote::create($baseData);
        }

        if ($quoteTypeId === QuoteTypeId::Health) {
            $healthData = array_merge($baseData, array_filter([
                'health_plan_type_id' => $request->health_plan_type_id,
            ]));

            return HealthQuote::create($healthData);
        }

        $personalData = array_merge($baseData, array_filter([
            'quote_type_id' => $quoteTypeId,
            'business_type_of_insurance_id' => $request->business_type_of_insurance_id,
        ]));

        return PersonalQuote::create($personalData);
    }
}
