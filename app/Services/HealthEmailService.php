<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Enums\quoteTypeCode;
use App\Enums\LeadSourceEnum;
use App\Enums\UserStatusEnum;
use App\Services\BirdService;
use App\Enums\ApplicationStorageEnums;
use App\Services\SendEmailCustomerService;


class HealthEmailService extends BaseService
{
    protected $sendEmailCustomerService;
    protected $healthQuoteService;

    public function __construct(SendEmailCustomerService $sendEmailCustomerService = null, HealthQuoteService $healthQuoteService = null)
    {
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->healthQuoteService = $healthQuoteService;
    }

    public function sendHealthOCAEmail($lead)
    {
        $plans = $lead->plans;


        // Determine the email template ID
        $emailTemplateId = 0;

        // Build email data
        $emailData = $this->buildEmailData($lead, $plans);
        $quotePlansCount = is_countable($plans) ? count($plans) : 0;
        if ($quotePlansCount > 0) {
            info('Inside plans of count: '.$lead->uuid.'    ');
            $pdfData = [
                'plan_ids' => collect($plans)->take(5)->pluck('id')->toArray(),
                'quote_uuid' => $lead->uuid,
            ];
            $pdf = $this->healthQuoteService->exportPlansPdf(quoteTypeCode::Health, $pdfData, json_decode(json_encode(['quotes' => ['plans' => $plans], 'isDataSorted' => true])));
            if (isset($pdf['error'])) {
                info('Failed to generate PDF for UUID in health OCA email service: '.$lead->uuid.' Error: '.$pdf['error']);
            } else {
                $emailData->pdfAttachment = (object) $pdf;
                info('attaching pdf: '.$lead->uuid.'    ');
            }
        }

         $responseCode = $this->sendEmailCustomerService->sendHealthOCAEmail($emailTemplateId, $emailData, 'health-oca-email');
         if ( in_array([200,201],$responseCode)) {
            $birdService =new  BirdService();
            // Sending bird workflow email
            $bird = $birdService->sendAutomationWorkflow('/health-oca',$emailData);

         }

        return $responseCode;
    }


    public function buildEmailData($lead, $plans)
    {
        if (count($plans) == 0) {
            // No plans with available ratings, build email data for the specific case
            return $this->buildNoPlansEmailData($lead);
        } else {
            // Plans with available ratings exist, build email data for the different case
            return $this->buildPlansEmailData($lead, $plans);
        }
    }

    private function buildNoPlansEmailData($healthQuote)
    {
        $advisor = User::where('id', $healthQuote->advisor_id)->first();
        $previousAdvisor = User::where('id', $healthQuote->previous_advisor_id)->first();
        $emailData = $this->buildCommonEmailData($healthQuote, $advisor, $previousAdvisor);
        $emailData->isReAssignment = ! empty($previousAdvisor);
        return $emailData;
    }

    private function buildPlansEmailData($healthQuote, $plans)
    {
        $advisor = User::where('id', $healthQuote->advisor_id)->first();
        return (object) [
            'clientFullName' => $healthQuote->first_name.' '.$healthQuote->last_name,
            'customerName' => $healthQuote->first_name.' '.$healthQuote->last_name,
            'customerEmail' => $healthQuote->email,
            'mobilePhone' => (! empty($advisor->mobile_no) ? formatMobileNoDisplay($advisor->mobile_no) : ''),
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'landLine' => (! empty($advisor->landline_no) ? formatLandlineDisplay($advisor->landline_no) : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'documentUrl' => '',
            'healthQuoteId' => $healthQuote->code,
            'currentInsurer' => $healthQuote->currently_insured_with,
            'quoteLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$healthQuote->uuid, // DLA = Disable Lead Assignment
            'requestAdvisorLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$healthQuote->uuid.'/?assignAdvisor=true',
            'assignmentType' => $this->getAssignmentTypeText($healthQuote->assignment_type),
            'previousAdvisorName' => ! empty($previousAdvisor) ? $previousAdvisor->name : '',
            'previousAdvisorStatus' => ! empty($previousAdvisor) ? UserStatusEnum::getUserStatusText($previousAdvisor->status) : '',
            'isReAssignment' => ! empty($previousAdvisor),
        ];
    }

    private function getAssignmentTypeText($assignmentType)
    {
        $assignmentText = '';
        switch ($assignmentType) {
            case 1:
                $assignmentText = 'System Assigned';
                break;
            case 2:
                $assignmentText = 'System ReAssigned';
                break;
            case 3:
                $assignmentText = 'Manual Assigned';
                break;
            case 4:
                $assignmentText = 'Manual ReAssigned';
                break;
            default:
                break;
        }

        return $assignmentText;
    }

    private function buildCommonEmailData($healthQuote, $advisor, $previousAdvisor)
    {
        $documentUrl = getAppStorageValueByKey(ApplicationStorageEnums::HEALTH_OCA_EMAIL_ATTACHMENT_URL);
        $whatsAppNumber = ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '';
        return (object) [
            'clientFullName' => $healthQuote->first_name.' '.$healthQuote->last_name,
            'customerName' => $healthQuote->first_name.' '.$healthQuote->last_name,
            'customerEmail' => $healthQuote->email,
            'mobilePhone' => (! empty($advisor->mobile_no) ? formatMobileNoDisplay($advisor->mobile_no) : ''),
            'whatsAppNumber' => $whatsAppNumber,
            'landLine' => (! empty($advisor->landline_no) ? formatLandlineDisplay($advisor->landline_no) : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'documentUrl' => '',
            'healthQuoteId' => $healthQuote->code,
            'currentInsurer' => $healthQuote->currently_insured_with,
            'quoteLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$healthQuote->uuid,
            'requestAdvisorLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$healthQuote->uuid,
            'assignmentType' => $this->getAssignmentTypeText($healthQuote->assignment_type),
            'previousAdvisorName' => ! empty($previousAdvisor) ? $previousAdvisor->name : '',
            'previousAdvisorStatus' => ! empty($previousAdvisor) ? UserStatusEnum::getUserStatusText($previousAdvisor->status) : '',
            'isReAssignment' => ! empty($previousAdvisor),
        ];
    }
}
