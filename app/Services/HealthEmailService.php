<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\User;
use App\Models\QuoteFlowDetails;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteFlowType;

class HealthEmailService extends BaseService
{
    public function sendHealthOCBIntroEmail($lead, $triggerSICWorkFlow)
    {
        // Retrieve plans with available ratings for the given lead
        info("sic sendHealthOCBEmail - Ref ID: {$lead->uuid}| Time: ".now());
        if ($triggerSICWorkFlow) {
            if (! $lead->sic_flow_enabled) {
                $advisor = User::where('id', $lead->advisor_id)->first();
                $emailData = $this->mapDataForFollowupEmail($lead, $advisor);
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

    private function mapDataForFollowupEmail($lead, $advisor)
    {
        return (object) [
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'refID' => $lead->code,
            'uuid' => $lead->uuid,
            'customerFullName' => $lead->first_name.' '.$lead->last_name,
            'advisorId' => $advisor->id ?? null,
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorDetails' => $advisor ?? null,
            'quotePlanLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid,
            'requestAdvisorLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid.'/?assignAdvisor=true',
            'quotePlanApiLink' => config('constants.KEN_API_ENDPOINT').'/get-health-quote-plans-order-priority?'.$lead->uuid.'&lang=en&isModified=true',
            'ApiToken' => config('constants.KEN_API_TOKEN'),
            'basicAuth' => 'Basic '.base64_encode(config('constants.KEN_API_USER').':'.config('constants.KEN_API_PWD')),
            'landLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'workflowType' => WorkflowTypeEnum::HEALTH_SIC_FOLLOWUPS,
            'customerMobile' => (! empty($lead->mobile_no) ? $lead->mobile_no : ''),
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::HEALTH, $lead->uuid),
            'instantAlfredLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid.'/?IA=true',
        ];
    }

    public function sendOCAHealthWorkFlow($lead)
    {
        info('Sending OCA Health followups email for lead: '.$lead->uuid.' | Time: '.now());
        if (! $lead->oca_flow_enabled) {
            $advisor = User::where('id', $lead->advisor_id)->first();
            $emailData = $this->buildOCAHealthFollowupEmailData($lead, $advisor);
            $sicEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW)->first();
            if ($sicEvent) {
                $response =app(BirdService::class)->triggerWebHookRequest($sicEvent->value, $emailData);
                $lead->oca_flow_enabled = true;
                $lead->save();
                info("OCA Health workflow event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());
                info("OCA Health workflow response: {$response->status_code} | Ref-ID: {$lead->uuid} |Time: ".now());

                if(!empty($response->headers['Run-Id'])) {
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

    public function buildOCAHealthFollowupEmailData($lead, $advisor)
    {
        return (object) [
            'quoteUID' => $lead->code,
            'customerEmail' => $lead->email,
            'uuid' => $lead->uuid,
            'refID' => $lead->uuid,
            'customerName' => $lead->first_name.' '.$lead->last_name,
            'advisorId' => $advisor->id ?? null,
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorDetails' => $advisor ?? null,
            'quotePlanLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid,
            'requestAdvisorLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid.'/?assignAdvisor=true',
            'quotePlanApiLink' => config('constants.KEN_API_ENDPOINT').'/get-health-quote-plans-order-priority?'.$lead->uuid.'&lang=en&isModified=true',
            'ApiToken' => config('constants.KEN_API_TOKEN'),
            'basicAuth' => 'Basic '.base64_encode(config('constants.KEN_API_USER').':'.config('constants.KEN_API_PWD')),
            'landLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'workflowType' => WorkflowTypeEnum::HEALTH_AUTOMATED_FOLLOWUPS,
            'customerMobile' => (! empty($lead->mobile_no) ? $lead->mobile_no : ''),
            'instantAlfredLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid.'/?IA=true',

        ];
    }

    public function createQuoteFlowDetails($lead, $response)
    {
        try {
            $runId = collect($response->headers['Run-Id'])->first();
            if (!empty($runId)) {
                QuoteFlowDetails::create([
                    'quote_uuid' => $lead->uuid,
                    'quote_type_id' => QuoteTypeId::Health,
                    'flow_type' => QuoteFlowType::HEALTH_AUTOMATED_FOLLOWUPS->value,
                    'flow_id' => $runId,
                ]);
                info("OCA Health workflow run id created for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }
            else {
                info("OCA Health workflow run id not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }
        } catch (\Throwable $th) {
            $errorMessage = "Error while creating quote flow details for lead: Ref-ID: {$lead->uuid} | Time: " . now();
            info($errorMessage);
            info("Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: " . now());
            throw $th;
        }
    }
}
