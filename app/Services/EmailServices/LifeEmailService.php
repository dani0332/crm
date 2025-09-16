<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\Life\LifeQuoteService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;

class LifeEmailService extends BaseService
{
    public function sendFICEmail(PersonalQuote $personalQuote)
    {

        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::FIC_LIFE_EMAIL)->first();

        LoggerService::info('| sendFICEmail - Initiating process');

        if(suppressIntroEmailByStatus($personalQuote->quote_status_id)) {
            LoggerService::info('sendFICEmail - Suppressing OCB Email because for');
            return;
        }

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
            $plansData = $this->checkPlans($personalQuote->uuid);
            // Optimize plan status checks and logging for clarity and maintainability
            if (! empty($plansData['hasError'])) {
                LoggerService::warning(
                    "sendFICEmail - Error checking plans: {$plansData['errorMessage']}, keeping lead status as NewLead"
                );

                return;
            }

            $totalPlans = (int) ($plansData['totalNumberOfPlans'] ?? 0);
            $hiddenPlans = (int) ($plansData['totalNumberOfHiddenPlans'] ?? 0);

            if ($totalPlans === 0) {
                LoggerService::info('sendFICEmail - No plans found, lead status remains NewLead');

                return;
            }

            if ($hiddenPlans === $totalPlans) {
                LoggerService::info('sendFICEmail - All plans are hidden, lead status remains NewLead');

                return;
            }

            if ($personalQuote->quote_status_id == QuoteStatusEnum::NewLead) {
                $personalQuote->quote_status_id = QuoteStatusEnum::Quoted;
                $personalQuote->save();
                LifeQuote::where('uuid', $personalQuote->uuid)->update(['quote_status_id' => QuoteStatusEnum::Quoted]);

            } else {
                LoggerService::info("sendFICEmail - Quote status is not new lead for quote: {$personalQuote->uuid}");
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
                'advanceBirthdayDate' => (string) $notifyDate->startOfDay()->timestamp,
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
        } else {
            LoggerService::info(self::class." - sendEmailAdvanceBirthdayWishToCustomer - Date of birth is not set for quote: {$personalQuote->uuid}");
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
                // Set birthdayDate to the start of the day (00:00:00) for sending the event
                'birthdayDate' => (string) $birthday->startOfDay()->timestamp,
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
        } else {
            LoggerService::info(self::class." - sendEmailBirthdayWishToCustomer - Date of birth is not set for quote: {$personalQuote->uuid}");
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
    private function checkPlans(string $quoteUID): array
    {
        try {
            $plansData = app(LifeQuoteService::class)->getQuotePlans($quoteUID);

            if (is_string($plansData)) {
                LoggerService::warning('checkPlans - API returned error', extra: [
                    'error' => $plansData,
                ]);

                return [
                    'totalNumberOfHiddenPlans' => 0,
                    'totalNumberOfPlans' => 0,
                    'hasError' => true,
                    'errorMessage' => $plansData,
                ];
            }

            if (! $plansData || ! isset($plansData->quotes) || ! isset($plansData->quotes->plans)) {
                LoggerService::warning('checkPlans - Invalid or empty plans data structure');

                return [
                    'totalNumberOfHiddenPlans' => 0,
                    'totalNumberOfPlans' => 0,
                    'hasError' => true,
                    'errorMessage' => 'Invalid plans data structure',
                ];
            }

            $plans = $plansData->quotes->plans;
            if (! is_array($plans) && ! is_object($plans)) {
                LoggerService::warning('checkPlans - Plans data is not iterable');

                return [
                    'totalNumberOfHiddenPlans' => 0,
                    'totalNumberOfPlans' => 0,
                    'hasError' => true,
                    'errorMessage' => 'Plans data is not iterable',
                ];
            }

            $totalNumberOfHiddenPlans = 0;
            $totalNumberOfPlans = is_array($plans) ? count($plans) : (is_countable($plans) ? count($plans) : 0);

            foreach ($plans as $plan) {
                if (isset($plan->isDisabled) && $plan->isDisabled) {
                    $totalNumberOfHiddenPlans++;
                }
            }

            LoggerService::info('checkPlans - Successfully processed plans', extra: [
                'totalPlans' => $totalNumberOfPlans,
                'hiddenPlans' => $totalNumberOfHiddenPlans,
            ]);

            return [
                'totalNumberOfHiddenPlans' => $totalNumberOfHiddenPlans,
                'totalNumberOfPlans' => $totalNumberOfPlans,
                'hasError' => false,
            ];

        } catch (\Exception $e) {
            LoggerService::error('checkPlans - Exception occurred', exception: $e);

            return [
                'totalNumberOfHiddenPlans' => 0,
                'totalNumberOfPlans' => 0,
                'hasError' => true,
                'errorMessage' => $e->getMessage(),
            ];
        }
    }
}
