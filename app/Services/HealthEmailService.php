<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Enums\quoteTypeCode;
use App\Enums\LeadSourceEnum;
use App\Models\ApplicationStorage;
use App\Enums\ApplicationStorageEnums;;
use App\Services\SendEmailCustomerService;



class HealthEmailService extends BaseService
{
    protected $sendEmailCustomerService;


    public function __construct(SendEmailCustomerService $sendEmailCustomerService)
    {
        $this->sendEmailCustomerService = $sendEmailCustomerService;
    }

    public function sendHealthOCBIntroEmail($plans, $lead, $previousAdvisorId, $healthQuoteService, $triggerSICWorkFlow = false)
    {
        $plans = $this->executePlansSelectionLogic((array)$plans);

        // Determine the email template ID
        $emailTemplateId = $this->getEmailTemplateId($lead, $plans,$triggerSICWorkFlow);

        // Build email data
        $emailData = $this->buildEmailData($lead, $plans, $previousAdvisorId);
        $quotePlansCount = is_countable($plans) ? count($plans) : 0;
        if ($quotePlansCount > 0) {
            info('Inside plans of count: '.$lead->uuid.'    ');
            $pdfData = [
                'plan_ids' => collect($plans)->take(5)->pluck('id')->toArray(),
                'quote_uuid' => $lead->uuid,
            ];
            $pdf = $healthQuoteService->exportPlansPdf(quoteTypeCode::Health, $pdfData, json_decode(json_encode(['quotes' => ['plans' => $plans], 'isDataSorted' => true])));
            if (isset($pdf['error'])) {
                info('Failed to generate PDF for UUID in car email service: '.$lead->uuid.' Error: '.$pdf['error']);
            } else {
                $emailData->pdfAttachment = (object) $pdf;
                info('attaching pdf: '.$lead->uuid.'    ');
            }
        }

        // trigger SIC workflow
        if ($triggerSICWorkFlow) {
            if (! $lead->sic_flow_enabled) {
                $sicEventName = ApplicationStorage::where('key_name', 'SIC_HEALTH_WORKFLOW_NAME')->first();
                if ($sicEventName) {
                    $apiResponse = SIBService::createWorkflowEvent($sicEventName->value, $lead, [], $emailData);
                    $lead->sic_flow_enabled = true;
                    $lead->save();
                    info('SIC Health workflow event triggered for lead: '.$lead->uuid.' and sic_flow_enabled: '.$lead->sic_flow_enabled);
                    info('SIC Health workflow response: '.$apiResponse);
                } else {
                    info('SIC Health workflow key not found');
                }
            } else {
                info('SIC Health workflow already enabled for lead: '.$lead->uuid);
            }
        }

        if ($lead->advisor_id) {
            $responseCode = $this->sendEmailCustomerService->sendHealthLMSIntroEmail($emailTemplateId, $emailData, 'lms-intro-email');
        } else {
            info('sendCarOCBIntroEmail - sendHealthNonAdvisorIntroEmail - Ref ID:'.$lead->uuid.' Time: '.now());
            $responseCode = $this->sendEmailCustomerService->sendHealthNonAdvisorIntroEmail($emailData, 'lms-intro-email', $emailTemplateId);
        }

        return $responseCode;
    }

    private function executePlansSelectionLogic(array $plans): array
    {
        $top6Plans = array_slice($plans, 0, 6);
        // return $top6Plans if $top6Plans is not empty otherwise return $plans
        return ! empty($top6Plans) ? $top6Plans : [];
    }

    public function buildEmailData($lead, $plans, $previousAdvisor)
    {
        return (object)[
            'healthQuoteId' => $lead->uuid,
            'customerEmail' => $lead->email,
            'customerFullName' => $lead->first_name.' '.$lead->last_name,
            'advisorId' => $lead->advisor_id,
            'advisorName' => $lead->advisor_name,
            'advisorEmail' => $lead->advisor_email,
            'plans' => $plans,
            'previousAdvisor' => $previousAdvisor,
        ];
    }

    private function getEmailTemplateId($lead, $plans, $triggerSICWorkFlow = false)
    {
        if ($triggerSICWorkFlow) {
            info('Inside sic flow enabled: '.$lead->uuid);
            $noAdvisorTemplateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::SIC_HEALTH_NO_ADVISOR_TEMPLATE)->first();
            if ($noAdvisorTemplateId) {
                return (int) $noAdvisorTemplateId->value;
            } else {
                info('SIC Health email template');
                $TemplateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::SIC_HEALTH_FOLLOWUP_TEMPLATE)->first();
                return (int) $TemplateId->value; // keeping it as a fallback
            }
        }
    }

}
