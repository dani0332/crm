<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\HealthPlanTypeEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Facades\Ken;
use App\Models\ApplicationStorage;
use App\Models\HealthQuote;
use App\Models\QuoteFlowDetails;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;

class HealthEmailService extends BaseService
{
    public function sendHealthOCBIntroEmail($lead, $triggerSICWorkFlow)
    {
        // Retrieve plans with available ratings for the given lead
        LoggerService::info("sic sendHealthOCBEmail - Ref ID: {$lead->uuid}| Time: ".now());
        if ($triggerSICWorkFlow) {
            if (! $lead->sic_flow_enabled) {
                $advisor = User::where('id', $lead->advisor_id)->first();
                $emailData = $this->mapDataForFollowupEmail($lead, $advisor, WorkflowTypeEnum::HEALTH_SIC_FOLLOWUPS);
                $sicEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW)->first();
                if ($sicEvent) {
                    $response = app(BirdService::class)->triggerWebHookRequest($sicEvent->value, $emailData);
                    $lead->sic_flow_enabled = true;
                    $lead->save();
                    LoggerService::info("SIC Health workflow event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());
                } else {
                    LoggerService::warning("SIC Health workflow key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
                }
            } else {
                LoggerService::info("SIC Health workflow already enabled for lead Ref-ID: {$lead->uuid} | Time: ".now());
            }
        } else {
            LoggerService::info("triggerSICWorkFlow: {$triggerSICWorkFlow} | - SIC Health workflow not enabled for lead Ref-ID: {$lead->uuid} | Time: ".now());
        }

        return $response ?? null;
    }

    private function mapDataForFollowupEmail($lead, $advisor, $workflowType)
    {
        return (object) [
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'refID' => $lead->code,
            'uuid' => $lead->uuid,
            'customerFullName' => "{$lead->first_name} {$lead->last_name}",
            'customerName' => "{$lead->first_name} {$lead->last_name}",
            'advisorId' => $advisor?->id ?? null,
            'advisorName' => $advisor?->name ?? '',
            'advisorEmail' => $advisor?->email ?? '',
            'advisorDetails' => $advisor ?? null,
            'quotePlanLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid,
            'requestAdvisorLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid.'/?assignAdvisor=true',
            'docUploadLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid.'/thankyou',
            'quotePlanApiLink' => config('constants.KEN_API_ENDPOINT').'/get-health-quote-plans-order-priority?'.$lead->uuid.'&lang=en&isModified=true',
            'landLine' => (! empty($advisor?->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor?->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor?->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor?->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'workflowType' => $workflowType,
            'customerMobile' => (! empty($lead->mobile_no) ? '+'.formatMobileNoWithoutPlus($lead->mobile_no) : ''),
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::HEALTH, $lead->uuid),
            'numberOfMembersCovered' => $workflowType == WorkflowTypeEnum::SIC_HEALTH_FOLLOWUPS_WA ? $lead->customerMembers->count() : null,
            'instantAlfredLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid.'/?IA=true',
        ];
    }

    private function getMembers($currentPlan)
    {
        if (! property_exists($currentPlan, 'memberPremiumBreakdown')) {
            return [];
        }

        return collect($currentPlan->memberPremiumBreakdown ?? [])
            ->map(function ($member, $index) {
                return collect($member)
                    ->merge([
                        'index' => $index + 1,
                        'dob' => isset($member['dob']) ? Carbon::parse($member['dob'])->format('d/m/Y') : null,
                        'ageValue' => isset($member['dob']) ? Carbon::parse($member['dob'])->age : null,
                    ])
                    ->when(isset($member['gender']), function ($collection) use ($member) {
                        return $collection->put('gender', strtoupper($member['gender']) === 'M' ? 'Male' : 'Female');
                    });
            })
            ->toArray();
    }

    private function includeHostInAttachmentPath(array $documents): array
    {
        if (empty($documents)) {
            return [];
        }

        $storageUrl = rtrim(config('constants.AZURE_IM_STORAGE_URL', ''), '/');

        return array_map(function (array $document) use ($storageUrl) {
            $link = $document['link'] ?? '';

            if (! empty($link) && ! Str::startsWith($link, ['http://', 'https://'])) {
                $document['link'] = $storageUrl.'/'.ltrim($link, '/');
            }

            return $document;
        }, $documents);
    }

    private function getValueFromRatesPerCopay($currentPlan, $property)
    {
        if ($currentPlan && property_exists($currentPlan, 'ratesPerCopay') && is_array($currentPlan->ratesPerCopay) && count($currentPlan->ratesPerCopay) > 0) {
            $ratesPerCopay = is_array($currentPlan->ratesPerCopay) ? $currentPlan->ratesPerCopay : (array) $currentPlan->ratesPerCopay;

            $ratesPerCopay = collect($ratesPerCopay)->first();

            return $ratesPerCopay[$property] ?? 0;
        }

        return null;
    }

    private function getPlanData($currentPlan)
    {
        if (! $currentPlan || ! is_object($currentPlan) || empty((array) $currentPlan)) {
            return [];
        }

        $plan = [];

        $getValueFromPlanOrRates = function (string $property) use ($currentPlan) {
            if (property_exists($currentPlan, $property) && ($currentPlan->$property ?? null)) {
                return $currentPlan->{$property};
            }

            return $this->getValueFromRatesPerCopay($currentPlan, $property);
        };

        $discountPremium = $getValueFromPlanOrRates('discountPremium');
        $vat = $getValueFromPlanOrRates('vat');

        if (property_exists($currentPlan, 'name')) {
            $plan['name'] = $currentPlan->name;
        }

        if (property_exists($currentPlan, 'providerName')) {
            $plan['providerName'] = $currentPlan->providerName;
        }

        if (property_exists($currentPlan, 'providerCode')) {
            $plan['providerCode'] = strtolower($currentPlan->providerCode);
        }

        if (property_exists($currentPlan, 'eligibilityName')) {
            $plan['tpa'] = $currentPlan->eligibilityName;
        }

        $plan['actualPremium'] = "AED {$discountPremium}";
        $plan['vat'] = "AED {$vat}";

        if (property_exists($currentPlan, 'policyWordings')) {
            $plan['tobs'] = $this->includeHostInAttachmentPath(array_map(fn ($item) => (array) $item, $currentPlan->policyWordings));
        }

        if (property_exists($currentPlan, 'benefits')) {
            $benefits = is_object($currentPlan->benefits) ? $currentPlan->benefits : (object) $currentPlan->benefits;

            if (property_exists($benefits, 'networkLink')) {
                $plan['networkLinks'] = $this->includeHostInAttachmentPath(array_map(fn ($item) => (array) $item, $benefits->networkLink));
            }
        }

        if (property_exists($currentPlan, 'mafLink')) {
            $plan['mafLink'] = $currentPlan->mafLink;
        }

        return $plan;
    }

    private function buildEmailDataForApplyNowEmail(HealthQuote $lead, ?User $advisor = null)
    {
        $response = Ken::request('/fetch-health-selected-plan', 'post', [
            'quoteUID' => $lead->uuid,
        ]);

        $plans = collect($response['plans'] ?? []);

        $currentPlan = (object) $plans->first();
        $members = $this->getMembers($currentPlan);
        $plan = $this->getPlanData($currentPlan);

        $payload = [
            'code' => $lead->code,
            'quoteUID' => $lead->uuid,
            'customerName' => "{$lead->first_name} {$lead->last_name}",
            'totalPremium' => "AED {$lead->premium}",
            'referenceCode' => $lead->code,
            'mobile' => $lead->mobile_no ?? '',
            'email' => $lead->email,
            'totalMembers' => count($members),
            'members' => $members,
            'plan' => $plan,
            'isCampaign' => getAppStorageValueByKey(ApplicationStorageEnums::IS_CAMPAIGN) == '1',
            'emirateOfYourVisaId' => $lead->emirate_of_your_visa_id,
        ];

        if ($advisor) {
            $payload['advisorDetails'] = [
                'id' => $advisor?->id,
                'name' => $advisor?->name,
                'email' => $advisor?->email ?? '',
                'mobileNo' => $advisor?->mobile_no ?? '',
                'landlineNo' => $advisor?->landline_no ?? '',
                'status' => $advisor?->status,
                'profilePhotoPath' => $advisor->profile_photo_path,
            ];
        }

        return (object) $payload;
    }

    public function initiateApplyNowEmail(HealthQuote $lead)
    {
        LoggerService::info(self::class." Inside Apply Now for uuid: {$lead->uuid}");
        try {
            if (! $lead->isApplicationPending()) {
                LoggerService::info(self::class." Skipping Apply Now Email becuase quote status is not application pending for uuid: {$lead->uuid}");

                return;
            }
            if ($lead->isAUHLead() && $lead->isLeadSourceRevivalOrInsuranceWallet()) {
                LoggerService::info(self::class." - Skipping Apply Now Email because lead is from AUH and Revival/Insurance Wallet for uuid: {$lead->uuid}");

                return;
            }

            $advisor = User::where('id', $lead->advisor_id)->first();
            $emailData = $this->buildEmailDataForApplyNowEmail($lead, $advisor);

            $responseCode = app(SendEmailCustomerService::class)->sendApplyNowEmail($emailData, $lead->isApplyNowEmailSent());

            if (in_array($responseCode, [200, 201])) {
                if (! $lead->isApplyNowEmailSent()) {
                    HealthQuote::withoutEvents(function () use ($lead) {
                        $lead->apply_now_email_sent_at = now();
                        $lead->save();
                    });
                    LoggerService::info(self::class." - Apply Now Email Sent to Customer Email: {$lead->email} Quote UuId: {$lead->uuid} with response code {$responseCode}");
                } elseif ($advisor) {
                    LoggerService::info(self::class." - Apply Now Email Sent to Advisor Email: {$advisor->email} Quote UuId: {$lead->uuid} with response code {$responseCode}");
                }

            } else {
                LoggerService::error(self::class." - Apply Now Email Not Sent: {$responseCode} Customer EmailAddress: {$lead->email} Quote UuId: {$lead->uuid}");
            }
        } catch (Exception $e) {
            LoggerService::error(self::class." - Exception for uuid {$lead->uuid}: ".$e->getMessage());
        }
    }

    public function sendOCAHealthWorkFlow($lead)
    {
        if ($lead->isAUHLead() && $lead->isLeadSourceRevivalOrInsuranceWallet()) {
            LoggerService::info(self::class." - Skipping OCA Health Workflow because lead is from AUH and Revival/Insurance Wallet for uuid: {$lead->uuid}");

            return;
        }

        LoggerService::info('Sending OCA Health followups email for lead: '.$lead->uuid.' | Time: '.now());
        if (! $lead->oca_flow_enabled) {
            $advisor = User::where('id', $lead->advisor_id)->first();
            $emailData = $this->mapDataForFollowupEmail($lead, $advisor, WorkflowTypeEnum::HEALTH_AUTOMATED_FOLLOWUPS);
            $birdSicHealthWorkflowData = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW)->first();
            if ($birdSicHealthWorkflowData) {
                $response = app(BirdService::class)->triggerWebHookRequest($birdSicHealthWorkflowData->value, $emailData);
                $lead->oca_flow_enabled = true;
                $lead->save();
                LoggerService::info("OCA Health workflow event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());
                LoggerService::info("OCA Health workflow response: {$response->status_code} | Ref-ID: {$lead->uuid} |Time: ".now());

                if (! empty($response->headers['Run-Id'])) {
                    $this->createQuoteFlowDetails($lead, $response);
                }
            } else {
                LoggerService::warning("OCA Health workflow key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }
        } else {
            LoggerService::info("OCA Health workflow already enabled for lead Ref-ID: {$lead->uuid} | Time: ".now());
        }

        return $response ?? null;
    }

    public function createQuoteFlowDetails($lead, $response)
    {
        try {
            $runId = collect($response->headers['Run-Id'])->first();
            if (! empty($runId)) {
                QuoteFlowDetails::create([
                    'quote_uuid' => $lead->uuid,
                    'quote_type_id' => QuoteTypeId::Health,
                    'flow_type' => QuoteFlowType::HEALTH_AUTOMATED_FOLLOWUPS->value,
                    'flow_id' => $runId,
                ]);
                LoggerService::info("OCA Health workflow run id created for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            } else {
                LoggerService::warning("OCA Health workflow run id not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }
        } catch (\Throwable $th) {
            $errorMessage = "Error while creating quote flow details for lead: Ref-ID: {$lead->uuid} | Time: ".now();
            LoggerService::error($errorMessage);
            LoggerService::error("Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: ".now());
            throw $th;
        }
    }

    public function sendApplicationSubmittedEmail($healthQuote)
    {
        try {
            LoggerService::info(self::class." - Inside for UUID: {$healthQuote->uuid}");
            $emailData = $this->mapDataForFollowupEmail($healthQuote, $healthQuote->advisor, WorkflowTypeEnum::HEALTH_APPLICATION_SUBMITTED);
            $workflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW);
            LoggerService::info(self::class." - Triggering Bird triggerWebHookRequest for UUID: {$healthQuote->uuid}");
            $response = app(BirdService::class)->triggerWebHookRequest($workflow, $emailData);
            LoggerService::info("Application submitted email sent for lead uuid: {$healthQuote->uuid} | Time: ".now());

            return $response;
        } catch (Exception $e) {
            LoggerService::error("Error sending application submitted email for lead Ref-ID: {$healthQuote->uuid} | Time: ".now().' - Error: '.$e->getMessage());

            return false;
        }
    }

    public function sendSICHealthFollowupsWA($lead)
    {
        $response = Ken::request('/get-health-cheapest-plans', 'post', [
            'quoteUID' => $lead->uuid,
            'isRequestForPlansWithExtendedData' => false,
            'isPlanTypes' => true,
        ]);

        if (empty($response['plans'])) {
            LoggerService::info('SIC Health Followups WA not executed because no plans found');

            return;
        }
        if (empty($response['planTypes'])) {
            LoggerService::info('SIC Health Followups WA not executed because no plan types found');

            return;
        }
        $planTypes = collect($response['planTypes'])
            ->mapWithKeys(function ($planType) {
                $key = $this->setPlanTypePremium($planType['text']);
                if ($key !== null) {
                    return [$key => $planType['calculatedDiscountPremium']];
                }

                return [];
            });

        try {
            $isFollowupExecuted = app(BirdService::class)->isFollowupExecuted($lead->uuid, QuoteTypes::HEALTH->id(), QuoteFlowType::SIC_HEALTH_FOLLOWUPS_WA->value);
            if ($isFollowupExecuted) {
                LoggerService::info('SIC Health Followups WA already executed');

                return;
            }

            $advisor = User::where('id', $lead->advisor_id)->first();
            $emailData = $this->mapDataForFollowupEmail($lead, $advisor, WorkflowTypeEnum::SIC_HEALTH_FOLLOWUPS_WA);
            $emailData->planTypes = $planTypes;
            $workflowURL = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW);
            $response = app(BirdService::class)->triggerWebHookRequest($workflowURL, $emailData);
            if (! empty($response->headers['Run-Id']) && in_array($response->status_code, [200, 201])) {
                app(BirdService::class)->createQuoteWorkFlowDetails($lead, $response, QuoteFlowType::SIC_HEALTH_FOLLOWUPS_WA->value, QuoteTypeId::Health);
                app(BirdService::class)->createQuoteWhatsAppFlowDetails($lead, WorkflowTypeEnum::SIC_HEALTH_FOLLOWUPS_WA, QuoteTypeId::Health);

                LoggerService::info('SIC Health Followups WA executed');
            }

        } catch (\Exception $exception) {
            LoggerService::error('Error sending SIC Health Followups WA ', exception: $exception);
        }

    }

    public function setPlanTypePremium($planType)
    {

        return match ($planType) {
            HealthPlanTypeEnum::typeText(HealthPlanTypeEnum::ENTRY_LEVEL->value) => 'entryLevelPremium',
            HealthPlanTypeEnum::typeText(HealthPlanTypeEnum::GOOD->value) => 'goodPremium',
            HealthPlanTypeEnum::typeText(HealthPlanTypeEnum::BEST->value) => 'bestPremium',
            default => null
        };
    }
}
