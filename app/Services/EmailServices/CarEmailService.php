<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CarPlanType;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteFlowType;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\UserStatusEnum;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\NBMotorFollowupEmailJob;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\QuoteFlowDetails;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\SendEmailCustomerService;
use App\Services\SIBService;
use Carbon\Carbon;

class CarEmailService extends BaseService
{
    protected $sendEmailCustomerService;

    public function __construct(SendEmailCustomerService $sendEmailCustomerService)
    {
        $this->sendEmailCustomerService = $sendEmailCustomerService;
    }

    public function sendCarOCBIntroEmail($plans, $lead, $tierR, $previousAdvisorId, $carQuoteService, $triggerSICWorkFlow = false, $triggerOnlyWorkflow = false, bool $forceSicWorkflow = false)
    {
        $plans = $this->executePlansSelectionLogic($plans);

        // Determine the email template ID
        $emailTemplateId = $this->getEmailTemplateId($lead, $plans, $tierR, $triggerSICWorkFlow);

        // Build email data
        $emailData = $this->buildEmailData($lead, $plans, $previousAdvisorId, $tierR->id);
        $quotePlansCount = is_countable($plans) ? count($plans) : 0;
        if ($quotePlansCount > 0) {
            info('Inside plans of count: '.$lead->uuid.'    ');
            $pdfData = [
                'plan_ids' => collect($plans)->take(5)->pluck('id')->toArray(),
                'quote_uuid' => $lead->uuid,
            ];
            $pdf = $carQuoteService->exportPlansPdf(quoteTypeCode::Car, $pdfData, json_decode(json_encode(['quotes' => ['plans' => $plans], 'isDataSorted' => true])));
            if (isset($pdf['error'])) {
                info('Failed to generate PDF for UUID in car email service: '.$lead->uuid.' Error: '.$pdf['error']);
            } else {
                $emailData->pdfAttachment = (object) $pdf;
                info('attaching pdf: '.$lead->uuid.'    ');
            }
        }

        // trigger SIC workflow
        if ($triggerSICWorkFlow || $forceSicWorkflow) {
            if (! $lead->sic_flow_enabled || $forceSicWorkflow) {
                $sicEventName = ApplicationStorage::where('key_name', 'SIC_WORKFLOW_NAME')->first();
                if ($sicEventName) {
                    $apiResponse = SIBService::createWorkflowEvent($sicEventName->value, $lead, [], $emailData);
                    if (! $lead->sic_flow_enabled) {
                        $lead->sic_flow_enabled = true;
                        $lead->save();
                    }
                    info('SIC workflow event triggered for lead: '.$lead->uuid.' and sic_flow_enabled: '.$lead->sic_flow_enabled);
                    info('SIC workflow response: '.$apiResponse);
                } else {
                    info('SIC workflow key not found');
                }
            } else {
                info('SIC workflow already enabled for lead: '.$lead->uuid);
            }
        }

        $responseCode = null;

        if (! $triggerOnlyWorkflow) {
            if ($lead->advisor_id) {
                $responseCode = $this->sendEmailCustomerService->sendLMSIntroEmail($emailTemplateId, $emailData, 'lms-intro-email');

                $nbFollowupDelayDuration = ApplicationStorage::where('key_name', ApplicationStorageEnums::NB_MOTOR_FOLLOWUP_DELAY_DURATION)->first();
                $nbFollowupDelayDuration = ! empty($nbFollowupDelayDuration->value) ? $nbFollowupDelayDuration->value : 24;
                NBMotorFollowupEmailJob::dispatch($lead->uuid)->delay(Carbon::now()->addHours((int) $nbFollowupDelayDuration));
                info('NBMotorFollowupEmailJob - Dispatched - Ref ID:'.$lead->uuid.' | Time: '.now());
            } else {
                info('sendCarOCBIntroEmail - sendNonAdvisorIntroEmail - Ref ID:'.$lead->uuid.' Time: '.now());
                $responseCode = $this->sendEmailCustomerService->sendNonAdvisorIntroEmail($emailData, 'lms-intro-email', $emailTemplateId);
                if ($responseCode) {
                    $this->sendEmailCustomerService->sendSICFollowupEmail($lead, QuoteTypes::CAR);
                    // after 24 hour email is being triggered from KEN api using bird flow
                }
            }
        }

        return $responseCode;
    }

    private function buildNoPlansEmailData($carQuote, $previousAdvisor, $tierRId)
    {
        $advisor = User::where('id', $carQuote->advisor_id)->first();

        $emailData = $this->buildCommonEmailData($carQuote, $advisor, $previousAdvisor);
        $emailData->isReAssignment = ! empty($previousAdvisor);
        if ($carQuote->source == LeadSourceEnum::RENEWAL_UPLOAD) {
            $emailData->isRenewal = true;
            $emailData->policyNumber = $carQuote->previous_quote_policy_number;
            $carbonDate = Carbon::parse($carQuote->previous_policy_expiry_date)->format('jS F Y');
            $emailData->renewalDueDate = $carbonDate;
        }
        // info('emailData: '.json_encode($emailData));

        return $emailData;
    }

    private function buildPlansEmailData($carQuote, $plans, $previousAdvisor, $tierRId)
    {
        $advisor = User::where('id', $carQuote->advisor_id)->first();
        $insurerPlans = [];
        foreach ($plans as $plan) {
            $insurerPlans[] = [
                'carValue' => $plan->repairType == CarPlanType::TPL ? 'N/A' : (empty($plan->carValue) ? 'N/A' : $plan->carValue),
                'excessAed' => empty($plan->excess) ? 'N/A' : $plan->excess,
                'repairType' => $this->getUpdateRepairType($plan->repairType, $plan->providerCode),
                'discountPremium' => ! empty($plan->discountPremium) ? number_format($plan->discountPremium, 2) : '',
                'planName' => $plan->name,
                'providerCode' => strtolower($plan->providerCode),
                'benefits' => $this->getPlanBenefits($plan),
                'buyNowLink' => $this->getPlanBuyNowLink($plan, $carQuote->uuid),
                'isRenewal' => ($plan->isRenewal ?? false),
            ];
        }

        $emailData = $this->buildCommonEmailData($carQuote, $advisor, $previousAdvisor);
        $emailData->plans = $insurerPlans;
        $emailData->totalPlans = count($insurerPlans);
        $emailData->isReAssignment = ! empty($previousAdvisor);

        if ($carQuote->source == LeadSourceEnum::RENEWAL_UPLOAD) {
            $emailData->isRenewal = true;
            $emailData->policyNumber = $carQuote->previous_quote_policy_number;
            $carbonDate = Carbon::parse($carQuote->previous_policy_expiry_date)->format('jS F Y');
            $emailData->renewalDueDate = $carbonDate;
        }

        return $emailData;
    }

    private function buildCommonEmailData($carQuote, $advisor, $previousAdvisor)
    {
        $documentUrl = getAppStorageValueByKey(ApplicationStorageEnums::LMS_INTRO_EMAIL_ATTACHMENT_URL);
        $whatsAppNumber = ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '';

        $isRevivalLead = $carQuote->source == LeadSourceEnum::REVIVAL || $carQuote->source == LeadSourceEnum::REVIVAL_PAID || $carQuote->source == LeadSourceEnum::REVIVAL_REPLIED;
        [$emailCampaignBanner, $emailCampaignBannerRedirectUrl] = getEmailCampaignBanner();

        return (object) [
            'clientFullName' => $carQuote->first_name.' '.$carQuote->last_name,
            'customerName' => $carQuote->first_name.' '.$carQuote->last_name,
            'customerEmail' => $carQuote->email,
            'mobilePhone' => (! empty($advisor->mobile_no) ? formatMobileNoDisplay($advisor->mobile_no) : ''),
            'whatsAppNumber' => $whatsAppNumber,
            'landLine' => (! empty($advisor->landline_no) ? formatLandlineDisplay($advisor->landline_no) : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'documentUrl' => ! $emailCampaignBanner ? [$documentUrl] : [],
            'carQuoteId' => $carQuote->code,
            'yearOfManufacture' => $carQuote->year_of_manufacture,
            'vehicleName' => $this->getVehicleName($carQuote),
            'currentInsurer' => $carQuote->currently_insured_with,
            'quoteLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$carQuote->uuid.($isRevivalLead ? '?dla=true' : ''), // DLA = Disable Lead Assignment
            'requestAdvisorLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$carQuote->uuid.'/?assignAdvisor=true',
            'assignmentType' => getAssignmentTypeText($carQuote->assignment_type),
            'previousAdvisorName' => ! empty($previousAdvisor) ? $previousAdvisor->name : '',
            'previousAdvisorStatus' => ! empty($previousAdvisor) ? UserStatusEnum::getUserStatusText($previousAdvisor->status) : '',
            'isReAssignment' => ! empty($previousAdvisor),
            'wfsBanner' => $emailCampaignBanner,
            'wfsBannerRedirectUrl' => $emailCampaignBannerRedirectUrl,
        ];
    }

    private function getPlanBenefits($plan)
    {
        $planAddons = []; // Initialize an empty array to store plan addons.

        foreach ($plan->addons as $addon) {
            // Initialize a flag to determine if this addon should be included.
            $shouldInclude = array_reduce($addon->carAddonOption, function ($carry, $option) {
                // Check if any option is selected; return true if found.
                return $carry || $option->isSelected;
            }, false);

            // If at least one option was selected, include this addon in the benefits.
            if ($shouldInclude && $addon->text !== 'Priority repair, 12 free car washes, VIP lane for RTA testing and more with AG cars') {
                $planAddons[] = [
                    'value' => $addon->text,
                ];
            }
        }

        return $planAddons;
    }

    private function getPlanBuyNowLink($plan, $uuid)
    {
        $buyNowLink = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$uuid.'/payment/?planId='.$plan->id.'&providerCode='.$plan->providerCode;

        return $buyNowLink;
    }

    private function getVehicleName($lead)
    {
        $vehicleName = '';
        if ($lead->car_make_id != null) {
            $carMake = CarMake::find($lead->car_make_id);

            if ($carMake) {
                $vehicleName = $carMake->text;
            }
        }

        if ($lead->car_model_id != null) {
            $carModel = CarModel::find($lead->car_model_id);

            if ($carModel) {
                // Update $vehicleName with car model text
                $vehicleName .= ' '.$carModel->text;
            }
        }
        if ($lead->car_model_detail_id != null) {
            $carModelDetail = CarModelDetail::find($lead->car_model_detail_id);

            if ($carModelDetail) {
                // Update $vehicleName with car model detail text
                $vehicleName .= ' '.$carModelDetail->text;
            }
        }

        return $vehicleName;
    }

    private function getUpdateRepairType($repairType, $providerCode)
    {
        $coreInsurer = ['AXA', 'OIC', 'TM', 'QIC', 'RSA'];
        $halfLiveInsurer = ['SI', 'OI', 'Watania', 'DNIRC', 'NIA', 'UI', 'IHC', 'NT'];
        if ($repairType == CarPlanType::COMP) {
            if (in_array($providerCode, $coreInsurer)) {
                $result = 'Premium workshop';
            } elseif (in_array($providerCode, $halfLiveInsurer)) {
                $result = 'Non-Agency workshop';
            } else {
                $result = 'NON-AGENCY';
            }
        } else {
            $result = $repairType;
        }

        return $result;
    }

    private function getEmailTemplateId($lead, $plans, $tierR, $triggerSICWorkFlow = false)
    {
        if ($triggerSICWorkFlow) {
            info('Inside sic flow enabled: '.$lead->uuid);
            $noAdvisorTemplateId = ApplicationStorage::where('key_name', 'SIC_NO_ADVISOR_TEMPLATE_ID')->first();
            if ($noAdvisorTemplateId) {
                return (int) $noAdvisorTemplateId->value;
            } else {
                return 605; // keeping it as a fallback
            }
        }
        if (count($plans) == 0) {
            // No plans with available ratings, send a specific email template
            return $lead->tier_id == $tierR->id ? 492 : 494;
        } else {
            // Plans with available ratings exist, send a different email template
            return $lead->tier_id == $tierR->id ? 491 : 493;
        }
    }

    public function buildEmailData($lead, $plans, $previousAdvisor, $tierRId)
    {
        if (count($plans) == 0) {
            // No plans with available ratings, build email data for the specific case
            return $this->buildNoPlansEmailData($lead, $previousAdvisor, $tierRId);
        } else {
            // Plans with available ratings exist, build email data for the different case
            return $this->buildPlansEmailData($lead, $plans, $previousAdvisor, $tierRId);
        }
    }

    private function executePlansSelectionLogic(array $plans): array
    {
        // Sort plans from lowest to highest by discount premium
        usort($plans, function ($a, $b) {
            return $a->discountPremium <=> $b->discountPremium;
        });

        // Check if there are any 'Comp' plans
        $compPlans = array_filter($plans, function ($plan) {
            // Check if the 'repairType' and 'isRatingAvailable' properties exist and meet the conditions.
            return property_exists($plan, 'repairType') &&
                property_exists($plan, 'isRatingAvailable') &&
                ($plan->repairType === CarPlanType::COMP || $plan->repairType === CarPlanType::AGENCY) &&
                $plan->isRatingAvailable === true;
        });

        if (count($compPlans) > 0) {
            // If 'Comp' plans exist, return the top 6 'Comp' plans
            $top6Plans = array_slice($compPlans, 0, 6);
        } else {
            // If there are no 'Comp' plans, return the top 6 plans
            $filteredPlans = array_filter($plans, function ($plan) {
                return $plan->repairType == CarPlanType::TPL && $plan->isRatingAvailable == true;
            });

            $top6Plans = array_slice($filteredPlans, 0, 6);
        }

        // return $top6Plans if $top6Plans is not empty otherwise return $plans
        return ! empty($top6Plans) ? $top6Plans : [];
    }

    public function sendSICNotificationToAdvisor($lead, $user)
    {
        return $this->sendEmailCustomerService->sendSICNotificationToAdvisor($lead, $user, QuoteTypes::CAR->value);
    }

    public function sendNBMotorWorkFlow($lead)
    {
        try {
            info('Sending NBMotorWorkFlow followups email for lead: '.$lead->uuid.' | Time: '.now());
            if (empty($lead->nb_flow_executed_at)) {
                $advisor = User::where('id', $lead->advisor_id)->first();
                $emailData = $this->buildNBMotorFollowupEmailData($lead, $advisor, WorkflowTypeEnum::NEW_BUSINESS_MOTOR_AUTOMATED_FOLLOWUPS);
                $birdMotorEventNB = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->first();
                if ($birdMotorEventNB) {
                    $response = app(BirdService::class)->triggerWebHookRequest($birdMotorEventNB->value, $emailData);
                    info("NBMotorWorkFlow event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());
                    info("NBMotorWorkFlow response: {$response->status_code} | Ref-ID: {$lead->uuid} |Time: ".now());
                    $lead->nb_flow_executed_at = now();
                    info("NBMotorWorkFlow lead ref-id: {$lead->uuid}| Quote StatusID: {$lead->quote_status_id} | Time: ".now());
                    $lead->save();

                    if (! empty($response->headers['Run-Id'])) {
                        $this->createQuoteFlowDetails($lead, $response);
                    }
                } else {
                    info("NBMotorWorkFlow key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
                }
            } else {
                info("NBMotorWorkFlow already executed: {$lead->nb_flow_executed_at}  for lead Ref-ID: {$lead->uuid} | Time: ".now());
            }

            return $response ?? null;
        } catch (\Throwable $th) {
            $errorMessage = "NBMotorWorkFlow-Error: while sending quote workflow for lead: Ref-ID: {$lead->uuid} | Time: ".now();
            info($errorMessage);
            info("NBMotorWorkFlow-Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: ".now());
            throw $th;
        }
    }
    public function buildNBMotorFollowupEmailData($lead, $advisor, $type, $templateType = null)
    {
        return (object) [
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'uuid' => $lead->uuid,
            'refID' => $lead->code,
            'customerFullName' => $lead->first_name.' '.$lead->last_name,
            'advisorId' => $advisor->id ?? null,
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorDetails' => $advisor ?? null,
            'quotePlanLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$lead->uuid,
            'requestAdvisorLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$lead->uuid.'/?assignAdvisor=true',
            'quotePlanApiLink' => config('constants.KEN_API_ENDPOINT').'/get-health-quote-plans-order-priority?'.$lead->uuid.'&lang=en&isModified=true',
            'landLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'workflowType' => $type,
            'templateType' => $templateType ?? null,
            'customerMobile' => (! empty($lead->mobile_no) ? $lead->mobile_no : ''),
            'instantAlfredLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$lead->uuid.'/?IA=true',
            'createdAt' => $lead->created_at,
        ];
    }

    public function createQuoteFlowDetails($lead, $response)
    {
        try {
            $runId = collect($response->headers['Run-Id'])->first();
            if (! empty($runId)) {
                QuoteFlowDetails::create([
                    'quote_uuid' => $lead->uuid,
                    'quote_type_id' => QuoteTypeId::Car,
                    'flow_type' => QuoteFlowType::NEW_BUSINESS_MOTOR_AUTOMATED_FOLLOWUPS->value,
                    'flow_id' => $runId,
                ]);
                info("NBMotorWorkFlow  run id created for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            } else {
                info("NBMotorWorkFlow  run id not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }
        } catch (\Throwable $th) {
            $errorMessage = "NBMotorWorkFlow-Error: while creating quote flow details for lead: Ref-ID: {$lead->uuid} | Time: ".now();
            info($errorMessage);
            info("NBMotorWorkFlow-Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: ".now());
            throw $th;
        }
    }

    // sending followups events for car quotes
    public function sendFollowupsEventForNB($lead, $templateType)
    {
        try {
            info('Sending NBEventFollowup followups email for lead: '.$lead->uuid.' | Time: '.now());
            $advisor = User::where('id', $lead->advisor_id)->first();
            $emailData = $this->buildNBMotorFollowupEmailData($lead, $advisor, WorkflowTypeEnum::NEW_BUSINESS_MOTOR_EVENT_FOLLOWUPS, $templateType);
            $birdMotorNBEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->first();
            if ($birdMotorNBEvent) {
                $response = app(BirdService::class)->triggerWebHookRequest($birdMotorNBEvent->value, $emailData);
                info("NBEventFollowup event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());
                info("NBEventFollowup response: {$response->status_code} | Ref-ID: {$lead->uuid} |Time: ".now());
                info("NBEventFollowup lead ref-id: {$lead->uuid}| Quote StatusID: {$lead->quote_status_id} | Time: ".now());
            } else {
                info("NBEventFollowup key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }
        } catch (\Throwable $th) {
            $errorMessage = "NBEventFollowup-Error: while sending quote workflow for lead: Ref-ID: {$lead->uuid} | Time: ".now();
            info($errorMessage);
            info("NBEventFollowup-Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: ".now());
            throw $th;
        }
    }
}
