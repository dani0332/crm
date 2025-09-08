<?php 

namespace App\Services\EmailServices;

use App\Services\BaseService;
use App\Models\ClaimRequest;
use App\Services\Logger\LoggerService;
use App\Models\User;
use App\Enums\WorkflowTypeEnum;
use App\Enums\ApplicationStorageEnums;
use App\Services\BirdService;

class ClaimEmailService extends BaseService
{
    public function sendIntroEmail($claimRequest)
    {
        $claimRequest = ClaimRequest::where('uuid', $claimRequest->uuid)->first();
        LoggerService::startQuoteLogging($claimRequest->uuid);

        if (! $claimRequest) {
            LoggerService::error(self::class.' - ClaimRequest not found');
        }
        $advisor = User::where('id', $claimRequest->manager_id)->first() ?? null;


        $payload = [
            'customerEmail' => $claimRequest->email,
            'customerName' => $claimRequest->first_name.' '.$claimRequest->last_name,
            'quoteUID' => $claimRequest->uuid,
            'refID' => $claimRequest->code,
            'quoteType' => null,
            'advisor' => $advisor,
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorProfilePath' => (! empty($advisor->profile_photo_path) ? $advisor->profile_photo_path : ''),
            'landLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'workflowType' => WorkflowTypeEnum::CLAIM_INTRODUCTORY_EMAIL_TO_CUSTOMER,
        ];

        $customerNotificationWorkflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_CUSTOMER_NOTIFY_UNAVAILABLE_ADVIOSR_WORKFLOW);
        if (! empty($customerNotificationWorkflow)) {
            app(BirdService::class)->triggerWebHookRequest($customerNotificationWorkflow, (object) $payload);
            LoggerService::info(self::class." - sendIntroEmail - Webhook request sent to: {$customerNotificationWorkflow} ");
        } else {
            LoggerService::info(self::class.'- sendIntroEmail - Webhook URL not found in storage');
        }
    }
}