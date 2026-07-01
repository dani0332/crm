<?php

namespace App\Services\WAServices;

use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypeId;
use App\Enums\WorkflowTypeEnum;
use App\Models\CarQuote;
use App\Models\User;
use App\Services\BaseService;
use App\Services\EmailServices\WebEngageService;
use App\Services\Logger\LoggerService;

class CarWAService extends BaseService
{
    public function sendCarMissingDocReminder(CarQuote $carQuote): void
    {
        $advisor = User::where('id', $carQuote->advisor_id)->first();
        $payload = [
            'customerId' => $carQuote->customer_id ?? '',
            'firstName' => $carQuote->first_name ?? '',
            'lastName' => $carQuote->last_name ?? '',
            'customerEmail' => $carQuote->email,
            'customerName' => $carQuote->first_name.' '.$carQuote->last_name,
            'customerMobile' => (! empty($carQuote->mobile_no) ? formatMobileNo($carQuote->mobile_no) : ''),
            'advisor' => $advisor ?? null,
            'advisorName' => $advisor?->name ?? '',
            'advisorEmail' => $advisor?->email ?? '',
            'advisorLandLine' => (! empty($advisor?->landline_no) ? $advisor->landline_no : ''),
            'advisorMobilePhone' => (! empty($advisor?->mobile_no) ? $advisor->mobile_no : ''),
            'advisorWhatsAppNumber' => ! empty($advisor?->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'advisorMobileNoWithoutSpaces' => (! empty($advisor?->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'quoteUID' => $carQuote->uuid,
            'refID' => $carQuote->code,
            'CarMake' => $carQuote->carMake->text ?? null,
            'CarModel' => $carQuote->carModel->text ?? null,
            'workflowType' => WorkflowTypeEnum::CAR_MISSING_DOC_REMINDER,
        ];
        $response = app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::CAR_MISSING_DOC_REMINDER, (array) $payload);

        LoggerService::info('sendWhatsappNotificationToCustomer - Webhook request sent  with Ref-ID: '.$carQuote->uuid);
        app(WebEngageService::class)->createQuoteWorkFlowDetails($carQuote->uuid, QuoteFlowType::CAR_MISSING_DOC_REMINDER, QuoteTypeId::Car);
        LoggerService::info('Car Missing Doc Reminder WA Run-Id found and created quote flow details');

    }

}
