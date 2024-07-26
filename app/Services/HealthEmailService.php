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
        $plans = $this->executePlansSelectionLogic($plans);

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
            $responseCode = $this->sendEmailCustomerService->sendLMSIntroEmail($emailTemplateId, $emailData, 'lms-intro-email');
        } else {
            info('sendCarOCBIntroEmail - sendNonAdvisorIntroEmail - Ref ID:'.$lead->uuid.' Time: '.now());
            $responseCode = $this->sendEmailCustomerService->sendNonAdvisorIntroEmail($emailData, 'lms-intro-email', $emailTemplateId);
            if ($responseCode) {
                $this->sendEmailCustomerService->sendSICFollowupEmail($lead);
                // Dispatch the job with a 24 hours delay

                info('sendCarOCBIntroEmail - SICFollowupEmailJob Dispatched - Ref ID:'.$lead->uuid.' Time: '.now());
            }
        }

        return $responseCode;
    }

    private function executePlansSelectionLogic(array $plans): array
    {
        // Sort plans from lowest to highest by discount premium
        usort($plans, function ($a, $b) {
            return $a->discountPremium <=> $b->discountPremium;
        });

        $top6Plans = array_slice($plans, 0, 6);
        // return $top6Plans if $top6Plans is not empty otherwise return $plans
        return ! empty($top6Plans) ? $top6Plans : [];
    }

    public function buildEmailData($lead, $plans, $previousAdvisor)
    {
        return (object)[
            'lead' => $lead,
            'plans' => $plans,
            'previousAdvisor' => $previousAdvisor,
        ];
    }

    private function getEmailTemplateId($lead, $plans, $triggerSICWorkFlow = false)
    {
        if ($triggerSICWorkFlow) {
            info('Inside sic flow enabled: '.$lead->uuid);
            $noAdvisorTemplateId = ApplicationStorage::where('key_name', 'SIC_HEALTH_NO_ADVISOR_TEMPLATE_ID')->first();
            if ($noAdvisorTemplateId) {
                return (int) $noAdvisorTemplateId->value;
            } else {
                return 0; // keeping it as a fallback
            }
        }
        if (count($plans) == 0) {
            // No plans with available ratings, send a specific email template
            return 0;
        } else {
            // Plans with available ratings exist, send a different email template
            return 0;
        }
    }

}
