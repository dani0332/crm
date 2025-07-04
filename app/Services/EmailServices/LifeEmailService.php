<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;

class LifeEmailService extends BaseService
{
    public function sendFICEmail(PersonalQuote $personalQuote)
    {

        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::FIC_LIFE_EMAIL)->first();

        LoggerService::info('| sendFICEmail - Initiating process');

        if ($workflowUrl && ! empty($workflowUrl->value)) {
            // Fetch the advisor
            $advisor = User::find($personalQuote->advisor_id);
            if (! $advisor) {
                LoggerService::info('sendFICEmail - Advisor not found');
            }
            $emailData = $this->buildEmailData($personalQuote, $advisor, WorkflowTypeEnum::LIFE_FIC_EMAIL);

            $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, $emailData);

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

    public function sendEmailAdvanceBirthdayWishToCustomer(PersonalQuote $personalQuote, $emailData, $workflowUrl)
    {
        $isFollowupExecuted = app(BirdService::class)
            ->isFollowupExecuted($personalQuote->uuid, QuoteTypes::LIFE->id(), QuoteFlowType::LIFE_ADVANCE_BIRTHDAY_WISH->value);

        if ($isFollowupExecuted) {
            LoggerService::info(self::class." - sendEmailAdvanceBirthdayWishToCustomer - Followup already executed {$personalQuote->uuid}");

            return;
        }

        $dateOfBirth = $personalQuote->dob;
        if ($dateOfBirth) {
            // Parse the birthday (defaults to current year)
            $dateOfBirth = Carbon::parse($dateOfBirth)->format('m-d');
            $birthday = Carbon::createFromFormat('m-d', $dateOfBirth);
            // Subtract 15 days to get the notification date
            $notifyDate = $birthday->subDays(15);
            // Check if the notification date is in the future
            if ($notifyDate->isPast()) {
                LoggerService::info(self::class." - sendEmailAdvanceBirthdayWishToCustomer - Notification date is in the past for quote: {$personalQuote->uuid}, notify date: {$notifyDate->format('Y-m-d')}");

                return;
            }
            $notifyBirthdayDate = [
                'advanceBirthdayDate' => (string) $notifyDate->timestamp,
                'advanceBirthdayDateString' => $notifyDate->format('Y-m-d'),
            ];

            $emailData->workflowType = WorkflowTypeEnum::LIFE_ADVANCE_BIRTHDAY_WISH_EMAIL;
            $emailData = array_merge((array) $emailData, $notifyBirthdayDate);
            $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl, $emailData);

            if ($response && $response->status_code === 200) {
                LoggerService::info('sendEmailAdvanceBirthdayWishToCustomer - Successfully triggered event');
                app(BirdService::class)->createQuoteWorkFlowDetails($personalQuote, $response, QuoteFlowType::LIFE_ADVANCE_BIRTHDAY_WISH->value, QuoteTypes::LIFE->id());

            } else {
                LoggerService::info("sendEmailAdvanceBirthdayWishToCustomer - Error triggering event having response status code: {$response?->status_code}");
            }
        }
    }

    public function sendEmailBirthdayWishToCustomer(PersonalQuote $personalQuote, $emailData, $workflowUrl)
    {
        $isFollowupExecuted = app(BirdService::class)
            ->isFollowupExecuted($personalQuote->uuid, QuoteTypes::LIFE->id(), QuoteFlowType::LIFE_BIRTHDAY_WISH->value);

        if ($isFollowupExecuted) {
            LoggerService::info(self::class." - sendEmailBirthdayWishToCustomer - Followup already executed {$personalQuote->uuid}");

            return;
        }

        $dateOfBirth = $personalQuote->dob;
        if ($dateOfBirth) {
            $dateOfBirth = Carbon::parse($dateOfBirth)->format('m-d');
            $birthday = Carbon::createFromFormat('m-d', $dateOfBirth);

            // Check if the notification date is in the future
            if ($birthday->isPast()) {
                LoggerService::info(self::class." - sendEmailBirthdayWishToCustomer - Notification date is in the past for quote: {$personalQuote->uuid}, notify date: {$birthday->format('Y-m-d')}");

                return;
            }

            $notifyBirthdayDate = [
                'birthdayDate' => (string) $birthday->timestamp,
                'birthdayDateString' => $birthday->format('Y-m-d'),
            ];
            $emailData->workflowType = WorkflowTypeEnum::LIFE_BIRTHDAY_WISH_EMAIL;
            $emailData = array_merge((array) $emailData, $notifyBirthdayDate);
            $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl, $emailData);

            if ($response && $response->status_code === 200) {
                LoggerService::info('sendEmailBirthdayWishToCustomer - Successfully triggered event');
                app(BirdService::class)->createQuoteWorkFlowDetails($personalQuote, $response, QuoteFlowType::LIFE_BIRTHDAY_WISH->value, QuoteTypes::LIFE->id());
            } else {
                LoggerService::info("sendEmailBirthdayWishToCustomer - Error triggering event having response status code: {$response?->status_code}");
            }
        }
    }

    public function sendAutomatedLifeFollowup(PersonalQuote $personalQuote)
    {
        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::FIC_LIFE_EMAIL)->first();

        LoggerService::info('| sendAutomatedLifeFollowup - Initiating process');

        if ($workflowUrl && ! empty($workflowUrl->value)) {
            // Fetch the advisor
            $advisor = User::find($personalQuote->advisor_id);
            if (! $advisor) {
                LoggerService::info("sendAutomatedLifeFollowup - Advisor not found for quote: {$personalQuote->uuid}");
            }
            $emailData = $this->buildEmailData($personalQuote, $advisor, WorkflowTypeEnum::LIFE_AUTOMATED_FOLLOWUPS);

            $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, $emailData);
            $this->sendEmailAdvanceBirthdayWishToCustomer($personalQuote, $emailData, $workflowUrl->value);
            $this->sendEmailBirthdayWishToCustomer($personalQuote, $emailData, $workflowUrl->value);

            if ($response && $response->status_code === 200) {
                LoggerService::info("sendAutomatedLifeFollowup - Successfully triggered event for quote: {$personalQuote->uuid}");
                app(BirdService::class)->createQuoteWorkFlowDetails($personalQuote, $response, QuoteFlowType::LIFE_AUTOMATED_FOLLOWUPS->value, QuoteTypes::LIFE->id());
            } else {
                LoggerService::info("sendAutomatedLifeFollowup - Error triggering event having response status code: {$response?->status_code}");
            }
        } else {
            LoggerService::info(self::class." - Automated Life Followup is not set workflow url not found for quote: {$personalQuote->uuid}");
        }
    }
}
