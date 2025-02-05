<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\HealthQuote;
use App\Models\QuoteFlowDetails;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class HealthEmailService extends BaseService
{
    public function sendHealthOCBIntroEmail($lead, $triggerSICWorkFlow)
    {
        // Retrieve plans with available ratings for the given lead
        info("sic sendHealthOCBEmail - Ref ID: {$lead->uuid}| Time: ".now());
        if ($triggerSICWorkFlow) {
            if (! $lead->sic_flow_enabled) {
                $advisor = User::where('id', $lead->advisor_id)->first();
                $emailData = $this->mapDataForFollowupEmail($lead, $advisor, WorkflowTypeEnum::HEALTH_SIC_FOLLOWUPS);
                $sicEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW)->first();
                if ($sicEvent) {
                    $response = app(BirdService::class)->triggerWebHookRequest($sicEvent->value, $emailData);
                    $lead->sic_flow_enabled = true;
                    $lead->save();
                    info("SIC Health workflow event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());
                } else {
                    info("SIC Health workflow key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
                }
            } else {
                info("SIC Health workflow already enabled for lead Ref-ID: {$lead->uuid} | Time: ".now());
            }
        } else {
            info("triggerSICWorkFlow: {$triggerSICWorkFlow} | - SIC Health workflow not enabled for lead Ref-ID: {$lead->uuid} | Time: ".now());
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
            'customerMobile' => (! empty($lead->mobile_no) ? $lead->mobile_no : ''),
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::HEALTH, $lead->uuid),
            'instantAlfredLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid.'/?IA=true',
        ];
    }

    private function getMembers($currentPlan)
    {
        return collect($currentPlan->memberPremiumBreakdown ?? [])
            ->map(function ($member, $index) {
                return collect($member)
                    ->merge([
                        'index' => $index + 1,
                        'dob' => isset($member->dob) ? Carbon::parse($member->dob)->format('d/m/Y') : null,
                        'ageValue' => isset($member->dob) ? Carbon::parse($member->dob)->age : null,
                    ])
                    ->when(isset($member->gender), function ($collection) use ($member) {
                        return $collection->put('gender', strtoupper($member->gender) === 'M' ? 'Male' : 'Female');
                    });
            })
            ->toArray();
    }

    private function buildEmailDataForApplyNowEmail(HealthQuote $lead, ?User $advisor = null)
    {
        $currentPlan = $lead->getCurrentPlan();

        $members = $this->getMembers($currentPlan);

        $getDiscountPremium = function () use ($currentPlan) {
            if ($currentPlan->discountPremium ?? null) {
                return $currentPlan->discountPremium;
            }

            if ($currentPlan && property_exists($currentPlan, 'ratesPerCopay') && is_array($currentPlan->ratesPerCopay) && count($currentPlan->ratesPerCopay) > 0) {
                return $currentPlan->ratesPerCopay?->discountPremium ?? 0;
            }
        };

        $getVat = function () use ($currentPlan) {
            if ($currentPlan->vat ?? null) {
                return $currentPlan->vat;
            }

            if ($currentPlan && property_exists($currentPlan, 'ratesPerCopay') && is_array($currentPlan->ratesPerCopay) && count($currentPlan->ratesPerCopay) > 0) {
                return $currentPlan->ratesPerCopay?->vat ?? 0;
            }
        };

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
            'plan' => [
                'name' => $currentPlan?->name,
                'providerName' => $currentPlan?->providerName,
                'providerCode' => strtolower($currentPlan?->providerCode ?? ''),
                'tpa' => $currentPlan?->eligibilityName ?? '',
                'actualPremium' => "AED {$getDiscountPremium()}",
                'vat' => "AED {$getVat()}",
                'tobs' => array_map(fn ($item) => (array) $item, $currentPlan?->policyWordings ?? []),
                'networkLinks' => array_map(fn ($item) => (array) $item, $currentPlan?->benefits?->networkLink ?? []),
                'mafLink' => $currentPlan?->mafLink,
            ],
            'isCampaign' => getAppStorageValueByKey(ApplicationStorageEnums::IS_CAMPAIGN) == '1',
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
        info(self::class." Inside Apply Now for uuid: {$lead->uuid}");
        try {
            if (! $lead->isApplicationPending()) {
                info(self::class." Skipping Apply Now Email becuase quote status is not application pending for uuid: {$lead->uuid}");

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
                    info(self::class." - Apply Now Email Sent to Customer Email: {$lead->email} Quote UuId: {$lead->uuid} with response code {$responseCode}");
                } elseif ($advisor) {
                    info(self::class." - Apply Now Email Sent to Advisor Email: {$advisor->email} Quote UuId: {$lead->uuid} with response code {$responseCode}");
                }

            } else {
                Log::error(self::class." - Apply Now Email Not Sent: {$responseCode} Customer EmailAddress: {$lead->email} Quote UuId: {$lead->uuid}");
            }
        } catch (Exception $e) {
            Log::error(self::class." - Exception for uuid {$lead->uuid}: ".$e->getMessage());
        }
    }

    public function sendOCAHealthWorkFlow($lead)
    {
        info('Sending OCA Health followups email for lead: '.$lead->uuid.' | Time: '.now());
        if (! $lead->oca_flow_enabled) {
            $advisor = User::where('id', $lead->advisor_id)->first();
            $emailData = $this->mapDataForFollowupEmail($lead, $advisor, WorkflowTypeEnum::HEALTH_AUTOMATED_FOLLOWUPS);
            $birdSicHealthWorkflowData = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW)->first();
            if ($birdSicHealthWorkflowData) {
                $response = app(BirdService::class)->triggerWebHookRequest($birdSicHealthWorkflowData->value, $emailData);
                $lead->oca_flow_enabled = true;
                $lead->save();
                info("OCA Health workflow event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());
                info("OCA Health workflow response: {$response->status_code} | Ref-ID: {$lead->uuid} |Time: ".now());

                if (! empty($response->headers['Run-Id'])) {
                    $this->createQuoteFlowDetails($lead, $response);
                }
            } else {
                info("OCA Health workflow key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }
        } else {
            info("OCA Health workflow already enabled for lead Ref-ID: {$lead->uuid} | Time: ".now());
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
                info("OCA Health workflow run id created for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            } else {
                info("OCA Health workflow run id not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }
        } catch (\Throwable $th) {
            $errorMessage = "Error while creating quote flow details for lead: Ref-ID: {$lead->uuid} | Time: ".now();
            info($errorMessage);
            info("Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: ".now());
            throw $th;
        }
    }

    public function sendApplicationSubmittedEmail($healthQuote)
    {
        try {
            info(self::class." - Inside for UUID: {$healthQuote->uuid}");
            $emailData = $this->mapDataForFollowupEmail($healthQuote, $healthQuote->advisor, WorkflowTypeEnum::HEALTH_APPLICATION_SUBMITTED);
            $workflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW);
            info(self::class." - Triggering Bird triggerWebHookRequest for UUID: {$healthQuote->uuid}");
            $response = app(BirdService::class)->triggerWebHookRequest($workflow, $emailData);
            info("Application submitted email sent for lead uuid: {$healthQuote->uuid} | Time: ".now());

            return $response;
        } catch (Exception $e) {
            Log::error("Error sending application submitted email for lead Ref-ID: {$healthQuote->uuid} | Time: ".now().' - Error: '.$e->getMessage());

            return false;
        }
    }
}
