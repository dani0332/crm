<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Services\SendEmailCustomerService;


class HealthEmailService extends BaseService
{
    protected $sendEmailCustomerService;

    public function __construct(SendEmailCustomerService $sendEmailCustomerService)
    {
        $this->sendEmailCustomerService = $sendEmailCustomerService;
    }

    public function sendHealthOCAEmail($plans, $lead, $previousAdvisorId, $healthQuoteService)
    {
        $plans = $this->executePlansSelectionLogic($plans);

        // Determine the email template ID
        $emailTemplateId = $this->getEmailTemplateId($lead, $plans);

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
                info('Failed to generate PDF for UUID in health OCA email service: '.$lead->uuid.' Error: '.$pdf['error']);
            } else {
                $emailData->pdfAttachment = (object) $pdf;
                info('attaching pdf: '.$lead->uuid.'    ');
            }
        }
         $responseCode = $this->sendEmailCustomerService->sendHealthOCAEmail($emailTemplateId, $emailData, 'health-oca-email');

        return $responseCode;
    }

}
