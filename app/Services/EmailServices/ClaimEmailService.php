<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;

class ClaimEmailService extends BaseService
{
    public function sendIntroEmail($claim)
    {
        $isEnabled = getAppStorageValueByKey(ApplicationStorageEnums::CLAIM_INTRO_EMAIL_SWITCH, useCache: true);
        if (! $isEnabled) {
            LoggerService::info(self::class.' - Claim intro email is not enabled');

            return;
        }
        if (! $claim) {
            LoggerService::error(self::class.' - Claim not found');

            return;
        }
        LoggerService::startQuoteLogging($claim->uuid);
        $advisor = User::where('id', $claim->manager_id)->first() ?? null;

        $payload = [
            'customerEmail' => $claim->email,
            'customerName' => $claim->first_name.' '.$claim->last_name,
            'quoteUID' => $claim->uuid,
            'refID' => $claim->code,
            'quoteType' => QuoteTypes::getName($claim->quote_type_id)->value ?? null,
            'advisor' => $advisor,
            'source' => $claim->source,
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorProfilePath' => (! empty($advisor->profile_photo_path) ? $advisor->profile_photo_path : ''),
            'landLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'workflowType' => WorkflowTypeEnum::CLAIM_INTRODUCTORY_EMAIL_TO_CUSTOMER,
        ];

        $customerNotificationWorkflow = getAppStorageValueByKey(ApplicationStorageEnums::CLAIM_INTRO_EMAIL_WORKFLOW, useCache: true);
        if (! empty($customerNotificationWorkflow)) {
            $response = app(BirdService::class)->triggerWebHookRequest($customerNotificationWorkflow, (object) $payload);
            LoggerService::info(self::class." - sendIntroEmail - Webhook request sent to: {$customerNotificationWorkflow} ");
            app(BirdService::class)->createQuoteWorkFlowDetails($claim, $response, WorkflowTypeEnum::CLAIM_INTRODUCTORY_EMAIL_TO_CUSTOMER);
            LoggerService::info(self::class.' - sendIntroEmail - Workflow details created for claim: ');
        } else {
            LoggerService::info(self::class.'- sendIntroEmail - Webhook URL not found in storage');
        }
        LoggerService::info(self::class.' - sendIntroEmail - Claim intro email sent for claim: ');

        return true;
    }
}
