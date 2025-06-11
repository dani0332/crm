<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;

class LifeEmailService extends BaseService
{
    public function sendFICEmail(PersonalQuote $personalQuote)
    {
        LoggerService::startQuoteLogging(QuoteTypes::LIFE->refId($personalQuote->uuid));

        $lifeFICEmail = ApplicationStorage::where('key_name', ApplicationStorageEnums::FIC_LIFE_EMAIL)->first();

        LoggerService::info('| sendFICEmail - Initiating process');

        if ($lifeFICEmail && ! empty($lifeFICEmail->value)) {
            // Fetch the advisor
            $advisor = User::find($personalQuote->advisor_id);
            if (! $advisor) {
                LoggerService::info('sendFICEmail - Advisor not found');
            }
            $emailData = $this->buildEmailData($personalQuote, $advisor, WorkflowTypeEnum::LIFE_FIC_EMAIL);
            $response = app(BirdService::class)->triggerWebHookRequest($lifeFICEmail->value, $emailData);
            if ($response && $response->status_code === 200) {
                LoggerService::info('sendFICEmail - Successfully triggered event');
            } else {
                LoggerService::info("sendFICEmail - Error triggering event having response status code: {$response?->status_code}");
            }
        } else {
            LoggerService::info(self::class.' - FIC Life Email is not set');
        }
    }

    public function buildEmailData($lead, $advisor, $workflowType)
    {
        $data = [
            // Lead-related data
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'customerFullName' => trim("{$lead->first_name} {$lead->last_name}"),
            'refID' => $lead->code,
            'customerMobile' => $lead->mobile_no ?? '',
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::LIFE, $lead->uuid),
            // Advisor-related data
            'advisorId' => $advisor?->id,
            'advisorName' => $advisor?->name ?? '',
            'advisorEmail' => $advisor?->email ?? '',
            'advisorDetails' => $advisor ?? null,
            'advisorProfilePath' => (! empty($advisor->profile_photo_path) ? $advisor->profile_photo_path : ''),
            'landLine' => $advisor?->landline_no ?? '',
            'mobilePhone' => $advisor?->mobile_no ?? '',
            'whatsAppNumber' => $advisor?->mobile_no ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => $advisor?->mobile_no ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : '',
            // Workflow-related data
            'workflowType' => $workflowType,
        ];

        return (object) $data;
    }
}
