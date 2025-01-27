<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\UserStatusEnum;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\SICFollowupEmailJob;
use App\Models\ApplicationStorage;
use App\Models\TravelQuote;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\SendEmailCustomerService;
use App\Services\SIBService;
use App\Services\TravelQuoteService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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
            'customerEmail' => $lead->email,
            'whatsAppNumber' => $whatsAppNumber,
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

            return $this->sendEmailCustomerService->sendLMSIntroEmail($emailTemplateId, $emailData, 'lms-intro-email', QuoteTypes::TRAVEL);
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
        $travelRenewalEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_TRAVEL_RENEWALS_OCB)->first();
        if ($travelRenewalEvent) {
            $response = app(BirdService::class)->triggerWebHookRequest($travelRenewalEvent->value, $emailData);
            info("SendOCBTravelRenewalIntroEmail workflow event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());
            $lead->quote_status_id = QuoteStatusEnum::Quoted;
            $lead->save();

            return $response->status_code;
        } else {
            info("SendOCBTravelRenewalIntroEmail workflow key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
        }
    }
    public function sendTravelAllianceFailedAllocationEmail($lead)
    {
        $advisor = User::where('id', $lead->advisor_id)->first();

        $emailData = $this->buildCommonEmailData($lead, $advisor, null, WorkflowTypeEnum::TRAVEL_ALLIANCE_FAILED_ALLOCATION);
        $travelEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::TRAVEL_ALLIANCE_FAILED_ALLOCATION_EMAIL_EVENT_URL)->first();
        if ($travelEvent) {
            $response = app(BirdService::class)->triggerWebHookRequest($travelEvent->value, $emailData);
            info("sendTravelAllianceFailedAllocationEmail workflow event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());

            return $response->status_code;
        } else {
            info("sendTravelAllianceFailedAllocationEmail workflow key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
        }

        return null;
    }
}
