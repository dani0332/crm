<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\UserStatusEnum;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\DeleteTempOCBPDFFileJob;
use App\Jobs\SendAutomatedTravelFollowup;
use App\Jobs\SendAutomatedTravelRenewalFollowup;
use App\Jobs\SICFollowupEmailJob;
use App\Models\QuoteFlowDetails;
use App\Models\TravelQuote;
use App\Models\User;
use App\Services\BaseService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use App\Services\SendEmailCustomerService;
use App\Services\SIBService;
use App\Services\TravelQuoteService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TravelEmailService extends BaseService
{
    public $travelQuoteService;
    public $sendEmailCustomerService;

    public function __construct(TravelQuoteService $travelQuoteService, SendEmailCustomerService $sendEmailCustomerService)
    {
        $this->travelQuoteService = $travelQuoteService;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
    }

    private function getPlans(TravelQuote $lead, bool $handleZeroPlans = false): object
    {
        $defaultResponse = (object) [
            'all' => [],
            'adultPlans' => [],
            'seniorPlans' => [],
        ];

        if ($handleZeroPlans) {
            info(self::class.' - getPlans: returning empty plans array because of handleZeroPlans');
        } else {
            try {
                $quotePlans = $this->travelQuoteService->getQuotePlans($lead->uuid, [
                    'sortAndSeparate' => true,
                ]);

                if (isset($quotePlans->message) && $quotePlans->message != '') {
                    return $defaultResponse;
                }

                // Extract the plans if they exist, otherwise return an empty array
                $plans = isset($quotePlans?->quotes?->plans) ? $quotePlans?->quotes?->plans : (object) [];

                $adultPlans = property_exists($plans, 'adult') ? $plans->adult : [];
                $adultPlans = collect($adultPlans)->filter(fn ($plan) => ! $plan->isDisabled)->sortBy('discountPremium')->values()->all();
                $seniorPlans = property_exists($plans, 'senior') ? $plans->senior : [];
                $seniorPlans = collect($seniorPlans)->filter(fn ($plan) => ! $plan->isDisabled)->sortBy('discountPremium')->values()->all();

                return (object) [
                    'all' => [
                        ...$adultPlans,
                        ...$seniorPlans,
                    ],
                    'adultPlans' => $adultPlans,
                    'seniorPlans' => $seniorPlans,
                ];
            } catch (Exception $e) {
                Log::error(self::class." - Error getting quote plans: {$e->getMessage()} with stack trace {$e->getTraceAsString()}");
            }
        }

        return $defaultResponse;
    }

    private function getPlanBuyNowLink($plan, $uuid)
    {
        return config('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL')."{$uuid}/payment/?planId={$plan->id}&providerCode={$plan->providerCode}";
    }

    private function getMappedPlan(TravelQuote $lead, $plan): array
    {
        return [
            'discountPremium' => ! empty($plan->discountPremium) ? number_format($plan->discountPremium, 2) : '',
            'planName' => property_exists($plan, 'name') ? $plan->name : '',
            'providerCode' => property_exists($plan, 'providerCode') ? strtolower($plan->providerCode) : '',
            'providerName' => property_exists($plan, 'providerName') ? $plan->providerName : '',
            'buyNowLink' => $this->getPlanBuyNowLink($plan, $lead->uuid),
            'benefits' => property_exists($plan, 'benefits') ? $this->getPlanBenefits($plan->benefits) : 'N/A',
            'isRenewal' => ($plan->isRenewal ?? false),
        ];
    }

    private function getPlanBenefits($benefits)
    {
        $toExtract = collect([
            'travelEmergencyMedicalExpenses',
            'travelCancellationCurtailment',
            'travelPersonalAccident',
            'travelPersonalBaggage',
            'travelPersonalMoney',
        ]);

        $planFixtures = collect();

        if (isset($benefits)) {
            foreach ($benefits as $type => $benefits) {
                collect($benefits)->each(function ($benefit) use ($toExtract, &$planFixtures, $type) {
                    if ($toExtract->contains($benefit->code)) {
                        $exists = $planFixtures->firstWhere('code', $benefit->code);
                        if (! $exists) {
                            $benefit->type = $type;
                            $planFixtures->push($benefit);
                        }
                    }
                });
            }
        }

        $sortedFixtures = $toExtract->map(fn ($itemCode) => $planFixtures->firstWhere('code', $itemCode))->filter(fn ($item) => $item && strtolower($item->value) !== 'excluded');

        if ($sortedFixtures->count() === 0) {
            return 'N/A';
        }

        return $sortedFixtures->first()->text.' and much more...';
    }

    private function buildCommonEmailData(TravelQuote $lead, $advisor, $previousAdvisor, $workflowType = null): object
    {
        $whatsAppNumber = ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '';

        $isRevivalLead = $lead->source == LeadSourceEnum::REVIVAL || $lead->source == LeadSourceEnum::REVIVAL_PAID || $lead->source == LeadSourceEnum::REVIVAL_REPLIED;
        [$emailCampaignBanner, $emailCampaignBannerRedirectUrl] = getEmailCampaignBanner();

        return (object) [
            'clientFullName' => "{$lead->first_name} {$lead->last_name}",
            'customerName' => "{$lead->first_name} {$lead->last_name}",
            'customerFullName' => "{$lead->first_name} {$lead->last_name}",
            'customerId' => $lead->customer_id ?? '',
            'firstName' => $lead->first_name ?? '',
            'lastName' => $lead->last_name ?? '',
            'customerMobile' => ! empty($lead->mobile_no) ? '+'.formatMobileNoWithoutPlus($lead->mobile_no) : '',
            'customerEmail' => $lead->email,
            'whatsAppNumber' => $whatsAppNumber,
            'uniqueId' => (string) Str::ulid(),
            'landLine' => (! empty($advisor?->landline_no) ? formatLandlineDisplay($advisor?->landline_no) : ''),
            'mobilePhone' => (! empty($advisor?->mobile_no) ? formatMobileNoDisplay($advisor?->mobile_no) : ''),
            'mobileNoWithoutSpaces' => (! empty($advisor?->mobile_no) ? preg_replace('/\s+/', '', $advisor?->mobile_no) : ''),
            'advisorEmail' => (! empty($advisor?->email) ? $advisor?->email : ''),
            'advisorName' => (! empty($advisor?->name) ? $advisor?->name : ''),
            'travelQuoteId' => $lead->code,
            'action' => $lead->insurer_api_email_action,
            'imcrmLink' => QuoteTypes::TRAVEL->url($lead->uuid),
            'travelQuoteLink' => QuoteTypes::TRAVEL->quoteLink($lead->uuid, $isRevivalLead ? ['dla' => 'true'] : []), // DLA = Disable Lead Assignment
            'requestAdvisorLink' => QuoteTypes::TRAVEL->quoteLink($lead->uuid, ['assignAdvisor' => 'true']),
            'assignmentType' => getAssignmentTypeText($lead->assignment_type),
            'previousAdvisorName' => ! empty($previousAdvisor) ? $previousAdvisor?->name : '',
            'previousAdvisorStatus' => ! empty($previousAdvisor) ? UserStatusEnum::getUserStatusText($previousAdvisor?->status) : '',
            'isReAssignment' => ! empty($previousAdvisor),
            'wfsBanner' => $emailCampaignBanner,
            'wfsBannerRedirectUrl' => $emailCampaignBannerRedirectUrl,
            'workflowType' => $workflowType ?? null,
            'quoteUUID' => $lead->uuid,
            'quoteUID' => $lead->uuid,
            'refId' => $lead->code,
            'refID' => $lead->code,
        ];
    }

    private function getTravelersHeading(int $count, string $age, int $plansCount)
    {
        $traveler = Str::plural('traveler', $count);
        $plan = Str::plural('Plan', $plansCount);

        return "{$plan} for {$count} {$traveler} aged {$age}:";
    }

    private function buildPlansData(bool $hasPlansGroups, TravelQuote $lead, object $plans, Collection $members)
    {
        $plansData = [];

        if ($hasPlansGroups) {
            $adultTravelers = $members->where('age', '<', 65)->count();
            $plansData[0] = [
                'heading' => $this->getTravelersHeading($adultTravelers, '0 to 64', count($plans->adultPlans)),
                'plansList' => [],
            ];
            foreach (array_slice($plans->adultPlans, 0, 3) as $plan) {
                $plansData[0]['plansList'][] = $this->getMappedPlan($lead, $plan);
            }

            $seniorTravelers = $members->where('age', '>=', 65)->count();
            $plansData[1] = [
                'heading' => $this->getTravelersHeading($seniorTravelers, '65 and above', count($plans->seniorPlans)),
                'plansList' => [],
            ];
            foreach (array_slice($plans->seniorPlans, 0, 3) as $plan) {
                $plansData[1]['plansList'][] = $this->getMappedPlan($lead, $plan);
            }
        } else {
            $plansData[0] = [
                'heading' => '',
                'plansList' => [],
            ];

            foreach (array_slice($plans->all, 0, 6) as $plan) {
                $plansData[0]['plansList'][] = $this->getMappedPlan($lead, $plan);
            }
        }

        return $plansData;
    }

    private function buildEmailData(TravelQuote $lead, object $plans, $previousAdvisor)
    {
        $advisor = User::where('id', $lead->advisor_id)->first();

        $emailData = $this->buildCommonEmailData($lead, $advisor, $previousAdvisor);
        $emailData->isReAssignment = ! empty($previousAdvisor);
        $emailData->hasPlansGroups = count($plans->adultPlans) > 0 && count($plans->seniorPlans) > 0;
        $members = $lead->customerMembers;
        $emailData->totalTravelers = $members->count();
        $emailData->allPlansCount = count($plans->all);
        $emailData->plans = $this->buildPlansData($emailData->hasPlansGroups, $lead, $plans, $members) ?? [];

        return $emailData;
    }

    private function triggerSICWorkflow(TravelQuote $lead, $emailData, bool $forceSicWorkflow = false)
    {
        if (! $lead->sic_flow_enabled || $forceSicWorkflow) {
            $sicEventName = getAppStorageValueByKey(ApplicationStorageEnums::SIC_TRAVEL_WORKFLOW_ENABLE);
            if ($sicEventName) {
                $apiResponse = SIBService::createWorkflowEvent($sicEventName, $lead, eventData: $emailData);
                if (! $lead->sic_flow_enabled) {
                    $lead->sic_flow_enabled = true;
                    $lead->save();
                }
                info(self::class." - SIC workflow event triggered for lead: {$lead->uuid} and {$sicEventName}: {$lead->sic_flow_enabled}");
                info(self::class." - SIC workflow response: {$apiResponse}");
            } else {
                info(self::class.' - SIC workflow key not found');
            }
        } else {
            info(self::class." - SIC workflow already enabled for lead: {$lead->uuid}");
        }
    }

    public function sendTravelOCBIntroEmail(TravelQuote $lead, $previousAdvisorId, bool $triggerSICWorkFlow = false, bool $handleZeroPlans = false, bool $forceSicWorkflow = false)
    {

        if ($lead->isSuppressIntroEmail()) {
            LoggerService::info(self::class." -Skipping OCB Email because for UUID: {$lead->uuid}");

            return;
        }

        $plans = $this->getPlans($lead, $handleZeroPlans);

        $emailTemplateId = getAppStorageValueByKey(ApplicationStorageEnums::TRAVEL_EMAIL_TEMPLATE);
        $emailData = $this->buildEmailData($lead, $plans, $previousAdvisorId);

        $quotePlansCount = is_countable($plans->all) ? count($plans->all) : 0;

        if ($quotePlansCount > 0) {
            $pdfData = [
                'plan_ids' => collect($plans->all)->take(5)->pluck('id')->toArray(),
                'quote_uuid' => $lead->uuid,
            ];
            info(self::class." - Going to generate PDF for uuid: {$lead->uuid}");
            $pdf = $this->travelQuoteService->exportPlansPdf(quoteTypeCode::Travel, $pdfData, json_decode(json_encode(['quotes' => ['plans' => $plans->all], 'isDataSorted' => true])));
            if (isset($pdf['error'])) {
                info(self::class." - Failed to generate PDF for UUID: {$lead->uuid} Error: {$pdf['error']}");
            } else {
                $emailData->pdfAttachment = (object) $pdf;
                info(self::class." - attaching pdf: {$lead->uuid}");
            }
        }

        // trigger SIC workflow
        if ($triggerSICWorkFlow || $forceSicWorkflow) {
            $this->triggerSICWorkflow($lead, $emailData, $forceSicWorkflow);
        }

        if ($lead->advisor_id) {
            info(self::class." - Going to Send Intro Email for uuid: {$lead->uuid}");

            // Send intro email first
            $responseCode = $this->sendEmailCustomerService->sendLMSIntroEmail($emailTemplateId, $emailData, 'lms-intro-email', QuoteTypes::TRAVEL);
            // Only update status if email was successfully sent
            if (in_array($responseCode, [200, 201])) {
                if ($quotePlansCount > 0 && in_array($lead->quote_status_id, [QuoteStatusEnum::NewLead, QuoteStatusEnum::Qualified])) {
                    $this->updateTravelQuoteStatus($lead->uuid);
                }

            } else {
                LoggerService::info(self::class." - Intro email failed with code {$responseCode}, skipping automated followup for uuid: {$lead->uuid}");
            }

            return $responseCode;
        }

        info(self::class." - sendNonAdvisorIntroEmail - Ref ID: {$lead->uuid} Time: ".now());

        $responseCode = $this->sendEmailCustomerService->sendNonAdvisorIntroEmail($emailData, 'lms-intro-email', $emailTemplateId, QuoteTypes::TRAVEL);

        if ($responseCode) {
            // Dispatch the job with a 24 hours delay
            if (isLeadSic($lead->uuid)) {
                SICFollowupEmailJob::dispatch($lead->uuid, QuoteTypes::TRAVEL)->delay(now()->addminutes(1));
                info(self::class." - SICFollowupEmailJob Dispatched - Ref ID: {$lead->uuid} Time: ".now());
            } else {
                info(self::class." | SICFollowupEmailJob - No SIC - Ref ID: {$lead->uuid} Time: ".now());
            }

        }

        return $responseCode;
    }

    public function sendSICNotificationToAdvisor(TravelQuote $lead, User $user)
    {
        return $this->sendEmailCustomerService->sendSICNotificationToAdvisor($lead, $user, QuoteTypes::TRAVEL->value);
    }

    public function SendOCBTravelRenewalIntroEmail(TravelQuote $lead)
    {
        $advisor = User::where('id', $lead->advisor_id)->first();

        $emailData = $this->buildCommonEmailData($lead, $advisor, null, WorkflowTypeEnum::TRAVEL_RENEWALS_OCB);

        $response = app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::TRAVEL_RENEWALS_OCB, (array) $emailData);
        LoggerService::info("SendOCBTravelRenewalIntroEmail workflow event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());

        // Update lead status to Quoted
        if ($lead->quote_status_id == QuoteStatusEnum::NewLead) {
            $lead->quote_status_id = QuoteStatusEnum::Quoted;
            $lead->save();
        }

        // Trigger automated renewal followup after status is updated to Quoted
        if (in_array($response->status_code, [200, 201])) {
            SendAutomatedTravelRenewalFollowup::dispatch($lead->uuid)->delay(now()->addSeconds(10));
            LoggerService::info(self::class." - Automated Travel Renewal Followup dispatched after status updated to Quoted for quote: {$lead->uuid}");
        }

        return $response->status_code;

    }

    /**
     * Send automated travel renewal follow-up emails
     */
    public function sendAutomatedTravelRenewalFollowup(TravelQuote $travelQuote)
    {
        LoggerService::info('sendAutomatedTravelRenewalFollowup - Initiating process for Travel renewal quote');

        $advisor = User::find($travelQuote->advisor_id);
        if (! $advisor) {
            LoggerService::info("sendAutomatedTravelRenewalFollowup - Advisor not found for travel renewal quote: {$travelQuote->uuid}");

            return;
        }

        $emailData = $this->buildCommonEmailData($travelQuote, $advisor, null, WorkflowTypeEnum::TRAVEL_RENEWAL_AUTOMATED_FOLLOWUPS);

        app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::TRAVEL_RENEWAL_AUTOMATED_FOLLOWUPS, (array) $emailData);

        LoggerService::info('sendAutomatedTravelRenewalFollowup - Successfully triggered automated follow-up workflow');

        app(WebEngageService::class)->createQuoteWorkFlowDetails($travelQuote->uuid, QuoteFlowType::TRAVEL_RENEWAL_AUTOMATED_FOLLOWUPS->value, QuoteTypes::TRAVEL->id());
    }

    public function sendTravelQatarFailedAllocationEmail($lead)
    {
        $advisor = User::where('id', $lead->advisor_id)->first();

        $emailData = $this->buildFailedEmailData($lead, $advisor, null, WorkflowTypeEnum::TRAVEL_QATAR_FAILED_ALLOCATION);

        $response = app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::TRAVEL_QATAR_FAILED_ALLOCATION, (array) $emailData);
        info("sendTravelQatarFailedAllocationEmail workflow event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());

        return $response->status_code ?? 200;

    }
    private function buildFailedEmailData(TravelQuote $lead, $advisor, $previousAdvisor, $workflowType = null): object
    {
        return (object) [
            'clientFullName' => "{$lead->first_name} {$lead->last_name}",
            'customerName' => "{$lead->first_name} {$lead->last_name}",
            'customerFullName' => "{$lead->first_name} {$lead->last_name}",
            'customerId' => $advisor?->email ?? '',
            'firstName' => $advisor?->name ?? '',
            'customerEmail' => $advisor?->email ?? '',
            'lastName' => $advisor?->name ?? '',
            'customerMobile' => ! empty($advisor?->mobile_no) ? '+'.formatMobileNoWithoutPlus($advisor?->mobile_no) : '',

            'uniqueId' => (string) Str::ulid(),
            'landLine' => (! empty($advisor?->landline_no) ? formatLandlineDisplay($advisor?->landline_no) : ''),
            'mobilePhone' => (! empty($advisor?->mobile_no) ? formatMobileNoDisplay($advisor?->mobile_no) : ''),
            'mobileNoWithoutSpaces' => (! empty($advisor?->mobile_no) ? preg_replace('/\s+/', '', $advisor?->mobile_no) : ''),
            'advisorEmail' => (! empty($advisor?->email) ? $advisor?->email : ''),
            'advisorName' => (! empty($advisor?->name) ? $advisor?->name : ''),
            'travelQuoteId' => $lead->code,
            'action' => $lead->insurer_api_email_action,
            'imcrmLink' => QuoteTypes::TRAVEL->url($lead->uuid),
            'requestAdvisorLink' => QuoteTypes::TRAVEL->quoteLink($lead->uuid, ['assignAdvisor' => 'true']),
            'assignmentType' => getAssignmentTypeText($lead->assignment_type),
            'previousAdvisorName' => ! empty($previousAdvisor) ? $previousAdvisor?->name : '',
            'previousAdvisorStatus' => ! empty($previousAdvisor) ? UserStatusEnum::getUserStatusText($previousAdvisor?->status) : '',
            'isReAssignment' => ! empty($previousAdvisor),
            'workflowType' => $workflowType ?? null,
            'quoteUUID' => $lead->uuid,
            'quoteUID' => $lead->uuid,
            'refId' => $lead->code,
            'refID' => $lead->code,
        ];
    }
    /**
     * Creates quote flow details for tracking email campaigns
     */
    public function createQuoteFlowDetails($lead, $response)
    {
        try {
            $runId = collect($response->headers['Run-Id'])->first();
            if (! empty($runId)) {
                QuoteFlowDetails::create([
                    'quote_uuid' => $lead->uuid,
                    'quote_type_id' => QuoteTypeId::Travel,
                    'flow_type' => QuoteFlowType::TRAVEL_SIC_FOLLOWUPS->value,
                    'flow_id' => $runId,
                ]);
                LoggerService::info(self::class." - createQuoteFlowDetails  run id created for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            } else {
                LoggerService::info(self::class." - createQuoteFlowDetails  run id not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }
        } catch (\Throwable $th) {
            $errorMessage = self::class." - createQuoteFlowDetails-Error: while creating quote flow details for lead: Ref-ID: {$lead->uuid} | Time: ".now();
            LoggerService::error($errorMessage);
            LoggerService::error(self::class." - createQuoteFlowDetails-Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: ".now());

        }
    }

    private function buildAIGWorkflowData($lead, $advisor, $type, $templateType = null)
    {
        return (object) [
            'customerId' => $lead->customer_id ?? '',
            'uniqueId' => (string) Str::ulid(),
            'firstName' => $lead->first_name ?? '',
            'lastName' => $lead->last_name ?? '',
            'customerMobile' => ! empty($lead->mobile_no) ? '+'.formatMobileNoWithoutPlus($lead->mobile_no) : '',
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'uuid' => $lead->uuid,
            'refID' => $lead->code,
            'customerFullName' => $lead->first_name.' '.$lead->last_name,
            'advisorId' => $advisor->id ?? null,
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorDetails' => $advisor ?? null,
            'quotePlanLink' => config('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL').$lead->uuid,
            'requestAdvisorLink' => config('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL').$lead->uuid.'/?assignAdvisor=true',
            'landLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'workflowType' => $type,
            'templateType' => $templateType ?? null,
            'instantAlfredLink' => config('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL').$lead->uuid.'/?IA=true',
            'createdAt' => $lead->created_at,
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::TRAVEL, $lead->uuid),
        ];
    }

    public function sendTravelAIGWorkflow($lead)
    {
        try {
            LoggerService::info('Sending AIGWorkflow for travel');

            // Refresh the lead to get the latest state
            $lead->refresh();

            if (empty($lead->travel_aig_flow_executed_at)) {
                LoggerService::info('AIGWorkflow not executed yet, but should be already marked in the database. Skipping to avoid duplication.');

                return null;
            }

            $advisor = User::where('id', $lead->advisor_id)->first();
            $emailData = $this->buildAIGWorkflowData($lead, $advisor, WorkflowTypeEnum::TRAVEL_AIG_WORKFLOW);
            // using the same event for AIG and BIRD_TRAVEL_FLLOWUP_DEDICATED_WORKFLOW_URL and have a Travel AIG branch in that event workflow

            $response = app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::TRAVEL_AIG_WORKFLOW, (array) $emailData);
            LoggerService::info('AIGWorkflow event triggered for travel');

            return $response;

            return null;
        } catch (Exception $e) {
            LoggerService::error('AIGWorkflow-Error: while sending workflow for travel', exception: $e);
            throw $e;
        }
    }

    /**
     * Send automated travel follow-up emails
     * Note: PDF generation is handled via API endpoint /quotes/{quoteType}/get-plans-pdf-url called from Bird
     */
    public function sendAutomatedTravelFollowup(TravelQuote $travelQuote)
    {
        LoggerService::info('sendAutomatedTravelFollowup - Initiating process for Travel quote');

        $advisor = User::find($travelQuote->advisor_id);
        if (! $advisor) {
            LoggerService::info("sendAutomatedTravelFollowup - Advisor not found for travel quote: {$travelQuote->uuid}");
        }

        $emailData = $this->buildCommonEmailData($travelQuote, $advisor, null, WorkflowTypeEnum::TRAVEL_AUTOMATED_FOLLOWUPS);

        app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::TRAVEL_AUTOMATED_FOLLOWUPS, (array) $emailData);

        LoggerService::info('sendAutomatedTravelFollowup - Successfully triggered automated follow-up workflow');

        app(WebEngageService::class)->createQuoteWorkFlowDetails($travelQuote->uuid, QuoteFlowType::TRAVEL_AUTOMATED_FOLLOWUPS->value, QuoteTypes::TRAVEL->id());
    }

    /**
     * Handle automated follow-up when advisor is assigned
     */
    public function handleAutomatedFollowup(TravelQuote $travelQuote): void
    {
        try {
            // Check if automated follow-up is already executed to prevent duplicates
            $isFollowupExecuted = app(WebEngageService::class)
                ->isFollowupExecuted($travelQuote->uuid, QuoteTypes::TRAVEL->id(), QuoteFlowType::TRAVEL_AUTOMATED_FOLLOWUPS->value);

            if ($isFollowupExecuted) {
                LoggerService::info(self::class." - TRAVEL_AUTOMATED_FOLLOWUPS - Followup already executed {$travelQuote->uuid}");

                return;
            }

            // Dispatch automated travel follow-up job with a short delay
            SendAutomatedTravelFollowup::dispatch($travelQuote->uuid)->delay(now()->addSeconds(60));

            LoggerService::info(self::class." - TRAVEL_AUTOMATED_FOLLOWUPS - Dispatched for travel quote: {$travelQuote->uuid}");
        } catch (Exception $e) {
            LoggerService::error(self::class.' - Error dispatching automated travel follow-up', [], $e, ['ref_id' => $travelQuote->uuid]);
        }
    }

    /**
     * Generate PDF and create temporary URL for Bird workflow
     * Uses already fetched plans data to avoid duplicate API calls
     */
    private function attachTravelOCBPDFToEmail($quoteUID, $plans, int $pdfExpiry = 20)
    {
        try {
            LoggerService::info(self::class.' - attachTravelOCBPDFToEmail - Generating PDF for uuid: '.$quoteUID);

            // Use already fetched plans data instead of calling API again
            if (! isset($plans->quotes->plans) || empty($plans->quotes->plans)) {
                LoggerService::info(self::class.' - attachTravelOCBPDFToEmail - No plans available for uuid: '.$quoteUID);

                return '';
            }

            // Extract plan IDs from the correct structure
            $planIds = collect($plans->quotes->plans)
                ->take(5)
                ->pluck('id')
                ->toArray();

            if (empty($planIds)) {
                LoggerService::info(self::class.' - attachTravelOCBPDFToEmail - No valid plan IDs found for uuid: '.$quoteUID);

                return '';
            }

            // Generate PDF using existing travel quote service with already fetched plans
            $pdfData = [
                'plan_ids' => $planIds,
                'quote_uuid' => $quoteUID,
            ];

            $pdf = $this->travelQuoteService->exportPlansPdf(
                quoteTypeCode::Travel,
                $pdfData,
                json_decode(json_encode(['quotes' => ['plans' => $plans->quotes->plans], 'isDataSorted' => true]))
            );

            if (isset($pdf['error'])) {
                LoggerService::error(self::class.' - attachTravelOCBPDFToEmail - PDF generation failed: '.$pdf['error'].' for uuid: '.$quoteUID);

                return '';
            }

            // Get PDF content
            $pdfContent = $pdf['pdf']->output(); // Use output() to get raw PDF content

            LoggerService::info(self::class.' - attachTravelOCBPDFToEmail - Storing PDF temporarily for uuid: '.$quoteUID);

            // Generate a unique temporary file path (exactly like HomeEmailService)
            $tempFilePath = 'temp/'.uniqid().'.pdf';
            Storage::disk('azureIMPrivate')->put($tempFilePath, $pdfContent);

            LoggerService::info(self::class.' - attachTravelOCBPDFToEmail - PDF stored successfully at path: '.$tempFilePath.' for uuid: '.$quoteUID);

            // Generate a public URL using generic method
            try {
                $url = app(QuoteDocumentService::class)->getDocumentUrl(
                    $tempFilePath,
                    'azureIMPrivate',
                    $pdfExpiry
                );

                if (! $url) {
                    LoggerService::error(self::class.' - attachTravelOCBPDFToEmail - Failed to generate temporary URL: File does not exist for uuid: '.$quoteUID);

                    return '';
                }
            } catch (Exception $urlException) {
                LoggerService::error(self::class.' - attachTravelOCBPDFToEmail - Failed to generate temporary URL: '.$urlException->getMessage().' for uuid: '.$quoteUID, exception: $urlException);

                return '';
            }

            // Schedule deletion after expiry time
            $this->scheduleFileDeletion($tempFilePath, $pdfExpiry);

            LoggerService::info(self::class.' - attachTravelOCBPDFToEmail - Final URL for Bird workflow: '.$url.' for uuid: '.$quoteUID);

            return $url;
        } catch (Exception $e) {
            LoggerService::error(self::class." - attachTravelOCBPDFToEmail - Error: {$e->getMessage()} for uuid: {$quoteUID}", exception: $e);

            return '';
        }
    }

    /**
     * Schedule file deletion job (similar to Home service)
     */
    protected function scheduleFileDeletion($filePath, int $expiryMinutes = 20)
    {
        // Use the existing job to handle file deletion matching the URL expiry time
        DeleteTempOCBPDFFileJob::dispatch($filePath)->delay(now()->addMinutes($expiryMinutes));
    }

    public function attachTravelOCBPDF($quoteUID, $code = null)
    {
        try {
            LoggerService::info(self::class.' - attachTravelOCBPDF - Generating PDF Ref-ID: '.$quoteUID);

            $quotePlans = app(TravelQuoteService::class)->getQuotePlans($quoteUID);

            // Validate the response before passing to attachTravelOCBPDFToEmail
            if (! is_object($quotePlans) || ! isset($quotePlans->quotes->plans)) {
                LoggerService::error(self::class.' - attachTravelOCBPDF - Invalid quote plans structure for uuid: '.$quoteUID, context: ['ref_id' => $code]);

                return '';
            }

            // Use the existing private method with the fetched plans
            return $this->attachTravelOCBPDFToEmail($quoteUID, $quotePlans);
        } catch (Exception $e) {
            // Log the error details
            LoggerService::error(self::class." - Error: attachTravelOCBPDF - Error attaching PDF  | Message: {$e->getMessage()} | File: {$e->getFile()} | Line: {$e->getLine()}", context: ['ref_id' => $code]);

            return '';
        }
    }

    private function updateTravelQuoteStatus(string $uuid): void
    {
        try {
            $travelQuote = TravelQuote::where('uuid', $uuid)->first();
            if ($travelQuote && $travelQuote->quote_status_id == QuoteStatusEnum::NewLead) {
                $travelQuote->quote_status_id = QuoteStatusEnum::Quoted;
                $travelQuote->save();
            }
        } catch (Exception $e) {
            LoggerService::error(self::class." - Error: updateTravelQuoteStatus - Error updating travel quote status | Message: {$e->getMessage()} | File: {$e->getFile()} | Line: {$e->getLine()}", context: ['ref_id' => $uuid], exception: $e);
        }
    }
}
