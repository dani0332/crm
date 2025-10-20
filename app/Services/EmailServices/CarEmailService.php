<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CarPlanType;
use App\Enums\CarRegistrationType;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteFlowType;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\CompanyCarFollowupJob;
use App\Jobs\CompanyCarOCBJob;
use App\Jobs\DeleteTempOCBPDFFileJob;
use App\Jobs\NBMotorFollowupEmailJob;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\QuoteFlowDetails;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\CarQuoteService;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use App\Services\SIBService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

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
            LoggerService::info('Inside plans of count: ');
            $pdfData = [
                'plan_ids' => collect($plans)->take(5)->pluck('id')->toArray(),
                'quote_uuid' => $lead->uuid,
            ];
            $pdf = $carQuoteService->exportPlansPdf(quoteTypeCode::Car, $pdfData, json_decode(json_encode(['quotes' => ['plans' => $plans], 'isDataSorted' => true])));
            if (isset($pdf['error'])) {
                info('Failed to generate PDF for UUID in car email service: '.$lead->uuid.' Error: '.$pdf['error']);
            } else {
                $validationResult = $this->validatePdfFileSize($pdf['pdf']);

                if (! $validationResult['isValid']) {
                    LoggerService::error(self::class." - PDF validation failed: {$validationResult['message']}");

                    return;
                }

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

        $response = null;

        if (! $triggerOnlyWorkflow) {
            if ($lead->registration_type == CarRegistrationType::COMPANY && $lead->source != LeadSourceEnum::RENEWAL_UPLOAD) {
                return $this->sendCarCompanyOCBIntroEmail($lead);
            }
            if ($lead->advisor_id) {
                $response = $this->sendEmailCustomerService->sendCarIntroEmailWithAdvisor($lead);

                $nbFollowupDelayDuration = ApplicationStorage::where('key_name', ApplicationStorageEnums::NB_MOTOR_FOLLOWUP_DELAY_DURATION)->first();
                $nbFollowupDelayDuration = ! empty($nbFollowupDelayDuration->value) ? $nbFollowupDelayDuration->value : 24;
                NBMotorFollowupEmailJob::dispatch(quoteUuid: $lead->uuid)->delay(Carbon::now()->addHours((int) $nbFollowupDelayDuration));

                LoggerService::info('NBMotorFollowupEmailJob - Dispatched - Ref ID:'.$lead->uuid.' | Time: '.now());

            } else {
                LoggerService::info('sendCarOCBIntroEmail - sendNonAdvisorIntroEmail - Ref ID:'.$lead->uuid.' Time: '.now());

                $response = $this->sendEmailCustomerService->sendCarIntroEmailWithoutAdvisor($lead);
                if ($response) {
                    $this->sendEmailCustomerService->sendSICFollowupEmail($lead, QuoteTypes::CAR);
                    // after 24 hour email is being triggered from KEN api using bird flow
                }
            }
        }

        return $response;
    }

    private function sendCarCompanyOCBIntroEmail($lead)
    {
        LoggerService::info(self::class." -sendCarCompanyOCBIntroEmail company car ocb intro email- Ref ID: {$lead->uuid} ");
        CompanyCarOCBJob::dispatch($lead->uuid)->delay(Carbon::now()->addMinutes(1));

        if (empty($lead->nb_flow_executed_at)) {
            $companyCarFollowupDelayDuration = ApplicationStorage::where('key_name', ApplicationStorageEnums::COMPANY_CAR_FOLLOWUP_DELAY_DURATION)->first();
            $companyCarFollowupDelayDuration = ! empty($companyCarFollowupDelayDuration->value) ? $companyCarFollowupDelayDuration->value : 24;
            CompanyCarFollowupJob::dispatch($lead->uuid)->delay(Carbon::now()->addMinutes((int) $companyCarFollowupDelayDuration));
            LoggerService::info('CompanyCarFollowupJob - Dispatched - Ref ID:'.$lead->uuid.' | Time: '.now());
        }

        return null;
    }

    private function validatePdfFileSize($pdfObject): array
    {
        try {
            $pdfContent = $pdfObject->output();
            $fileSizeBytes = strlen($pdfContent);
            $fileSizeMB = round($fileSizeBytes / (1024 * 1024), 2);

            // Set maximum file size to 17MB
            $maxSizeBytes = 17 * 1024 * 1024; // 17MB

            $sizeMessage = "Size: {$fileSizeMB} MB";

            if ($fileSizeBytes > $maxSizeBytes) {
                return [
                    'isValid' => false,
                    'message' => "{$sizeMessage} exceeds 17MB limit",
                ];
            }

            return [
                'isValid' => true,
                'message' => $sizeMessage,
            ];

        } catch (\Exception $e) {
            return [
                'isValid' => false,
                'message' => 'Error calculating PDF size: '.$e->getMessage(),
            ];
        }
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
        $emailData->isReAssignment = $carQuote->isReAssignment();

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
                        $this->createQuoteFlowDetails($lead, $response, QuoteFlowType::NEW_BUSINESS_MOTOR_AUTOMATED_FOLLOWUPS->value);
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

    public function buildNBMotorFollowupEmailData($lead, $advisor, $type, $templateType = null, $pdfUrl = null)
    {
        return (object) [
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'refID' => $lead->code,
            'customerFullName' => $lead->first_name.' '.$lead->last_name,
            'companyName' => $lead->company_name ?? '',
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
            'pdfUrl' => $pdfUrl,
        ];
    }

    public function createQuoteFlowDetails($lead, $response, $flowType = null)
    {
        try {
            $runId = collect($response->headers['Run-Id'])->first();
            if (! empty($runId)) {
                QuoteFlowDetails::create([
                    'quote_uuid' => $lead->uuid,
                    'quote_type_id' => QuoteTypeId::Car,
                    'flow_type' => $flowType,
                    'flow_id' => $runId,
                    'started_at' => now(),
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

    public function sendPCPFollowups($lead)
    {
        try {
            info(self::class.' - Sending sendPCPFollowups followups email for lead: '.$lead->uuid.' | Time: '.now());
            $advisor = User::where('id', $lead->advisor_id)->first();
            $emailData = $this->buildNBMotorFollowupEmailData($lead, $advisor, WorkflowTypeEnum::MOTOR_PCP_FOLLOWUPS);
            $birdMotorPCPEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::MOTOR_PCP_FOLLOWUPS)->first();
            if ($birdMotorPCPEvent) {
                $response = app(BirdService::class)->triggerWebHookRequest($birdMotorPCPEvent->value, $emailData);
                if (! empty($response->headers['Run-Id'])) {
                    $this->createQuoteFlowDetails($lead, $response, QuoteFlowType::MOTOR_PCP_FOLLOWUPS->value);
                }
                LoggerService::info(self::class." - sendPCPFollowups event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());
                LoggerService::info(self::class." - sendPCPFollowups response: {$response->status_code} | Ref-ID: {$lead->uuid} |Time: ".now());
                LoggerService::info(self::class." - sendPCPFollowups lead ref-id: {$lead->uuid}| Quote StatusID: {$lead->quote_status_id} | Time: ".now());
            } else {
                LoggerService::info(self::class." - sendPCPFollowups key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }
        } catch (\Throwable $th) {
            $errorMessage = self::class." - sendPCPFollowups-Error: while sending quote workflow for lead: Ref-ID: {$lead->uuid} | Time: ".now();
            LoggerService::error($errorMessage);
            LoggerService::error(self::class." - sendPCPFollowups-Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: ".now());

        }
    }

    public function sendPCPOCBIntroEmail($lead)
    {
        try {
            LoggerService::info(self::class.' - Sending sendPCPOCBIntroEmail followups email for lead: '.$lead->uuid.' | Time: '.now());
            $advisor = User::where('id', $lead->advisor_id)->first();
            $emailData = $this->buildNBMotorFollowupEmailData($lead, $advisor, WorkflowTypeEnum::MOTOR_PCP_OCB);
            $birdMotorPCPEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::MOTOR_PCP_FOLLOWUPS)->first();
            if ($birdMotorPCPEvent) {
                $response = app(BirdService::class)->triggerWebHookRequest($birdMotorPCPEvent->value, $emailData);
                LoggerService::info(self::class." - sendPCPOCBIntroEmail event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());
                LoggerService::info(self::class." - sendPCPOCBIntroEmail response: {$response->status_code} | Ref-ID: {$lead->uuid} |Time: ".now());
                LoggerService::info(self::class." - sendPCPOCBIntroEmail lead ref-id: {$lead->uuid}| Quote StatusID: {$lead->quote_status_id} | Time: ".now());
            } else {
                info(self::class." - sendPCPOCBIntroEmail key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }
        } catch (\Throwable $th) {
            $errorMessage = self::class." - sendPCPOCBIntroEmail-Error: while sending quote workflow for lead: Ref-ID: {$lead->uuid} | Time: ".now();
            LoggerService::error($errorMessage);
            LoggerService::error(self::class." - sendPCPOCBIntroEmail-Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: ".now());

        }
    }
    public function sendAIGWorkflow($lead)
    {
        try {
            info('Sending AIGWorkflow for lead: '.$lead->uuid.' | Time: '.now());
            if (empty($lead->aig_flow_executed_at)) {
                $advisor = User::where('id', $lead->advisor_id)->first();
                $emailData = $this->buildAIGWorkflowData($lead, $advisor, WorkflowTypeEnum::AIG_WORKFLOW);
                // using the same event for AIG and NB Motor and have a AIG branch in that event workflow
                $birdAIGEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->first();

                if ($birdAIGEvent) {
                    $response = app(BirdService::class)->triggerWebHookRequest($birdAIGEvent->value, $emailData);
                    info("AIGWorkflow event triggered for lead Ref-ID: {$lead->uuid} | Time: ".now());
                    info("AIGWorkflow response: {$response->status_code} | Ref-ID: {$lead->uuid} | Time: ".now());

                    $lead->aig_flow_executed_at = now();
                    info("AIGWorkflow lead ref-id: {$lead->uuid} | Quote StatusID: {$lead->quote_status_id} | Time: ".now());
                    $lead->save();

                    if (! empty($response->headers['Run-Id'])) {
                        $this->createQuoteFlowDetails($lead, $response);
                    }
                } else {
                    info("AIGWorkflow key not found for lead: Ref-ID: {$lead->uuid} | Time: ".now());
                }
            } else {
                info("AIGWorkflow already executed: {$lead->aig_flow_executed_at} for lead Ref-ID: {$lead->uuid} | Time: ".now());
            }

            return $response ?? null;
        } catch (\Throwable $th) {
            $errorMessage = "AIGWorkflow-Error: while sending workflow for lead: Ref-ID: {$lead->uuid} | Time: ".now();
            info($errorMessage);
            info("AIGWorkflow-Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: ".now());
            throw $th;
        }
    }

    /**
     * Build data for AIG workflow
     */
    private function buildAIGWorkflowData($lead, $advisor, $type, $templateType = null)
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
            'landLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'workflowType' => $type,
            'templateType' => $templateType ?? null,
            'customerMobile' => (! empty($lead->mobile_no) ? $lead->mobile_no : ''),
            'instantAlfredLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$lead->uuid.'/?IA=true',
            'createdAt' => $lead->created_at,
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::CAR, $lead->uuid),
        ];
    }

    public function sendCompanyCarWorkFlow($lead)
    {
        try {
            LoggerService::info(self::class.' - Sending Company Car followups email for lead: '.$lead->uuid.' | Time: '.now());
            if (empty($lead->nb_flow_executed_at)) {
                $advisor = User::where('id', $lead->advisor_id)->first();
                $emailData = $this->buildNBMotorFollowupEmailData($lead, $advisor, WorkflowTypeEnum::COMPANY_CAR_AUTOMATED_FOLLOWUPS);
                $birdMotorEventNB = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->first();
                if ($birdMotorEventNB) {
                    $response = app(BirdService::class)->triggerWebHookRequest($birdMotorEventNB->value, $emailData);
                    LoggerService::info(self::class." - CompanyCarWorkFlow - Event triggered | Response: {$response->status_code} | Lead Ref-ID: {$lead->uuid} | Quote StatusID: {$lead->quote_status_id} | Time: ".now());
                    $lead->nb_flow_executed_at = now();

                    $lead->save();

                    if (! empty($response->headers['Run-Id'])) {
                        $this->createQuoteFlowDetails($lead, $response);
                    }
                } else {
                    LoggerService::info(self::class." - CompanyCarWorkFlow key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
                }
            } else {
                LoggerService::info(self::class." - CompanyCarWorkFlow already executed: {$lead->nb_flow_executed_at}  for lead Ref-ID: {$lead->uuid} | Time: ".now());
            }

            return $response ?? null;
        } catch (\Throwable $th) {
            $errorMessage = self::class." - CompanyCarWorkFlow-Error: while sending quote workflow for lead: Ref-ID: {$lead->uuid} | Time: ".now();
            LoggerService::error($errorMessage);
            LoggerService::error(self::class." - CompanyCarWorkFlow-Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: ".now());

        }
    }

    public function sendCompanyCarOCB($lead)
    {
        try {
            LoggerService::info('Sending Company Car OCB email for lead: '.$lead->uuid.' | Time: '.now());
            $advisor = User::where('id', $lead->advisor_id)->first();
            $pdfUrl = $this->attachCarOCBPDF($lead->uuid, $lead->code);
            $emailData = $this->buildNBMotorFollowupEmailData($lead, $advisor, WorkflowTypeEnum::COMPANY_CAR_OCB, pdfUrl: $pdfUrl);

            $birdMotorEventNB = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->first();
            if ($birdMotorEventNB) {
                $response = app(BirdService::class)->triggerWebHookRequest($birdMotorEventNB->value, $emailData);
                LoggerService::info("CompanyCarOCB - Event triggered | Response: {$response->status_code} | Lead Ref-ID: {$lead->uuid} | Quote StatusID: {$lead->quote_status_id} | Time: ".now());

                if (! empty($response->headers['Run-Id'])) {
                    $this->createQuoteFlowDetails($lead, $response);
                }
            } else {
                LoggerService::info("CompanyCarOCB key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }

            return $response ?? null;
        } catch (\Throwable $th) {
            $errorMessage = "CompanyCarOCB-Error: while sending quote workflow for lead: Ref-ID: {$lead->uuid} | Time: ".now();
            LoggerService::error($errorMessage);
            LoggerService::error("CompanyCarOCB-Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: ".now());

        }
    }

    public function attachCarOCBPDF($quoteUID, $code = null)
    {
        try {
            LoggerService::info(self::class.' - attachCarOCBPDF - Generating PDF Ref-ID: '.$quoteUID);

            $quotePlans = app(CarQuoteService::class)->getQuotePlans($quoteUID);

            // Generate the PDF
            $planIds = [];
            if (isset($quotePlans->quotes->plans)) {
                $planIds = collect($quotePlans->quotes->plans)
                    ->filter(function ($plan) {
                        return ! $plan->isDisabled && $plan->isRatingAvailable;
                    })
                    ->sortByDesc('isRenewal')
                    ->pluck('id')
                    ->take(5)
                    ->toArray() ?? [];
            }

            if (empty($planIds)) {
                LoggerService::info(self::class.' - attachCarOCBPDF - No plans found for Ref-ID: '.$quoteUID);

                return '';
            }

            $pdfFile = app(CarQuoteService::class)->exportPlansPdf(QuoteTypes::CAR->value, ['quote_uuid' => $quoteUID, 'plan_ids' => $planIds]);

            $pdfContent = $pdfFile['pdf']->output(); // Use output() to get raw PDF content

            // Generate a unique temporary file path
            $tempFilePath = 'temp/'.uniqid().'.pdf';
            Storage::disk('azureIM')->put($tempFilePath, $pdfContent);

            // Generate a public URL
            $publicUrl = Storage::disk('azureIM')->temporaryUrl(
                $tempFilePath,
                now()->addMinutes(10)
            );
            // Schedule deletion after 5 minutes
            $this->scheduleFileDeletion($tempFilePath);

            LoggerService::info(self::class.' - attachCarOCBPDF - Public URL generated for Ref-ID: '.$quoteUID.' | URL: '.$publicUrl);

            return $publicUrl;
        } catch (\Exception $e) {
            // Log the error details
            LoggerService::error(self::class." - Error: attachCarOCBPDF - Error attaching PDF  | Message: {$e->getMessage()} | File: {$e->getFile()} | Line: {$e->getLine()}", context: ['ref_id' => $code]);

            return '';
        }
    }
    protected function scheduleFileDeletion($filePath)
    {
        // Use a job to handle file deletion
        DeleteTempOCBPDFFileJob::dispatch($filePath)->delay(now()->addMinutes(5));
    }

    public function sendCarCompanyCommercialOCB($lead)
    {
        LoggerService::startQuoteLogging(QuoteTypes::CAR->refId($lead->uuid));
        try {
            LoggerService::info(self::class.' - Sending Car Company Commercial OCB email');
            $advisor = User::where('id', $lead->advisor_id)->first();
            $pdfUrl = $this->attachCarOCBPDF($lead->uuid, $lead->code);
            $emailData = $this->buildNBMotorFollowupEmailData($lead, $advisor, WorkflowTypeEnum::CAR_COMMERCIAL_OCB, pdfUrl: $pdfUrl);

            $birdMotorEventNB = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->first();
            if ($birdMotorEventNB) {
                $response = app(BirdService::class)->triggerWebHookRequest($birdMotorEventNB->value, $emailData);
                LoggerService::info(self::class.' - sendCarCompanyCommercialOCB - Event triggered ', ['response_status_code' => $response->status_code, 'lead_status_id' => $lead->quote_status_id]);

                if (! empty($response->headers['Run-Id'])) {
                    $this->createQuoteFlowDetails($lead, $response);
                }
            } else {
                LoggerService::info(self::class.' - sendCarCompanyCommercialOCB key not found ');
            }

            return $response ?? null;
        } catch (\Exception  $exception) {
            LoggerService::error(self::class.' - sendCarCompanyCommercialOCB - Error while sending quote workflow for lead ', exception: $exception);
        }
    }

    public function sendFailedCarRenewals($failedQuotes, $renewalsUploadLeadsId)
    {

        $workflow = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->first();
        if ($workflow) {
            $response = app(BirdService::class)->triggerWebHookRequest($workflow->value, $this->buildFailedCarRenewalsEmailData($failedQuotes, $renewalsUploadLeadsId));
            LoggerService::info(self::class.' - sendFailedCarRenewals - Event triggered ');
        }

    }

    public function buildFailedCarRenewalsEmailData($failedQuotes, $renewalsUploadLeadsId)
    {
        // Retrieve all CarRenewalManager emails in a single query
        $renewalsManagersEmails = User::role(RolesEnum::RenewalsManager)
            ->pluck('email')
            ->filter()
            ->values()
            ->all();

        // Get all failed renewal processes for the given policy numbers
        $failedPolicyNumbers = collect($failedQuotes)->unique()->values()->all();

        return (object) [
            'failedQuotes' => implode(', ', $failedPolicyNumbers),
            'quoteUID' => '', // Not used, reserved for future
            'renewalsManagersEmails' => $renewalsManagersEmails,
            'renewalManagerEmail' => $renewalsManagersEmails[0] ?? '',
            'workflowType' => WorkflowTypeEnum::CAR_CQF_RENEWALS_ERRORS,
            'dateOfAttempt' => now()->format('Y-m-d'),
            'failedLeadsCount' => count($failedPolicyNumbers) ?? 0,
            'fileDownloadUrl' => route('downloadValidationFailedFile', ['id' => $renewalsUploadLeadsId]),
        ];
    }
    public function sendFollowUpEmailForCQF($lead)
    {
        try {

            if (app(BirdService::class)->isFollowupExecuted($lead->uuid, QuoteTypeId::Car, QuoteFlowType::CAR_CQF_RENEWAL_FOLLOWUPS)) {
                LoggerService::info(self::class." - Follow Up Email for CQF already executed for lead: {$lead->uuid}");

                return;
            }

            LoggerService::info(self::class.' - Sending Follow Up Email for CQF');
            $advisor = User::where('id', $lead->advisor_id)->first();
            $emailData = $this->buildNBMotorFollowupEmailData($lead, $advisor, WorkflowTypeEnum::CAR_CQF_RENEWAL_FOLLOWUPS);
            $birdMotorEventNB = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->first();
            $birdCQFEvent = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW, false, true);
            if ($birdMotorEventNB) {
                $response = app(BirdService::class)->triggerWebHookRequest($birdCQFEvent, $emailData);
                app(BirdService::class)->createQuoteWorkFlowDetails($lead, $response, QuoteFlowType::CAR_CQF_RENEWAL_FOLLOWUPS, QuoteTypeId::Car);
                LoggerService::info(self::class." - sendFollowUpEmailForCQF - Event triggered successfully for lead: {$lead->uuid} ", ['response_status_code' => $response->status_code, 'lead_status_id' => $lead->quote_status_id]);
            }
        } catch (\Exception $exception) {
            LoggerService::error(self::class.' - sendFollowUpEmailForCQF - Error while sending quote workflow for lead ', exception: $exception);
        }
    }

}
