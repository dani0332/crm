<?php

namespace App\Services\WAServices;

use App\Services\BaseService;
use App\Models\CarQuote;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use App\Enums\ApplicationStorageEnums;
use App\Enums\WorkflowTypeEnum;
use App\Models\User;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypeId;
use PgSql\Lob;

class CarWAService extends BaseService
{
    public function sendCarMissingDocReminder(CarQuote $carQuote): void
    {
        $advisor = User::where('id', $carQuote->advisor_id)->first();
        $payload = [
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
        $carMissingDocReminderWorkflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_CAR_MISSING_DOC_REMINDER_WORKFLOW);
        if (! empty($carMissingDocReminderWorkflow)) {
            $response = app(BirdService::class)->triggerWebHookRequest($carMissingDocReminderWorkflow, (object) $payload);
            LoggerService::info('sendWhatsappNotificationToCustomer - Webhook request sent to: '.$carMissingDocReminderWorkflow.' with Ref-ID: '.$carQuote->uuid);
            
            $runId = "";
            if (isset($response->headers['Run-Id'])) {
                $runId = is_array($response->headers['Run-Id']) 
                    ? collect($response->headers['Run-Id'])->first() 
                    : $response->headers['Run-Id'];
            } elseif (isset($response->headers['run-id'])) {
                $runId = is_array($response->headers['run-id'])
                    ? collect($response->headers['run-id'])->first()
                    : $response->headers['run-id'];
            }
            LoggerService::info(self::class." - runId found: {$runId} for quote: {$carQuote->uuid}");
            if (! empty($runId)) {
                app(BirdService::class)->createQuoteWorkFlowDetails($carQuote, $response, QuoteFlowType::CAR_MISSING_DOC_REMINDER, QuoteTypeId::Car);
                LoggerService::info('Car Missing Doc Reminder WA Run-Id found and created quote flow details');
            }
        } else {
            LoggerService::info('sendWhatsappNotificationToCustomer - Webhook URL not found in storage with Ref-ID:'.$carQuote->uuid);
        }
    }


   

}