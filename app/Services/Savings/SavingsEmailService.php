<?php

declare(strict_types=1);

namespace App\Services\Savings;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Models\SavingsQuote;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use App\Services\Quotes\SavingsQuoteService;

class SavingsEmailService
{
    /**
     * Send OCA (One Click Apply) Email for Savings quote
     *
     * @param  string  $quoteUID  The quote UUID
     * @param  array  $data  Additional data (e.g., plan_ids)
     * @return mixed Response from Bird service or false on failure
     */
    public function sendOCAEmail(string $quoteUID, array $data = []): mixed
    {
        LoggerService::startQuoteLogging($quoteUID);

        $logPrefix = self::class.' fn: sendOCAEmail - ';

        LoggerService::info($logPrefix.' - Sending OCA email');

        $lead = $this->getQuote($quoteUID);

        if (! $lead) {
            LoggerService::warning($logPrefix.' - Quote not found');

            return false;
        }

        if ($lead->isSuppressIntroEmail()) {
            LoggerService::info('sendOCAEmail - Suppressing OCA Email');

            return null;
        }

        // Map data for bird service
        $emailData = $this->mapOCAEmailData($lead, $data);

        // Get bird flow url for Savings from ApplicationStorage
        $flowUrl = $this->getApplicationStorage();
        if (! $flowUrl) {
            LoggerService::info($logPrefix.' - Flow URL not found');

            return false;
        }

        try {
            $response = app(BirdService::class)->triggerWebHookRequest($flowUrl, $emailData);

            if ($response && $response->status_code == 200) {
                if ($lead->quote_status_id == QuoteStatusEnum::NewLead) {

                    $checkPlans = $this->checkPlans($lead->uuid);

                    if (isset($checkPlans['hasError']) && $checkPlans['hasError']) {
                        LoggerService::warning("sendOCAEmail - Error checking plans: {$checkPlans['errorMessage']}, keeping lead status as NewLead");

                        return $response;
                    }

                    if ($checkPlans['totalNumberOfPlans'] == 0) {
                        LoggerService::info('sendOCAEmail - total number of plans is 0, so lead status will remain NewLead');

                        return $response;
                    }

                    if ($checkPlans['totalNumberOfHiddenPlans'] == $checkPlans['totalNumberOfPlans']) {
                        LoggerService::info('sendOCAEmail - total number of hidden plans is equal to total number of plans, so lead status will remain NewLead');

                        return $response;
                    }

                    LoggerService::info('sendOCAEmail - changing lead status to Quoted', [
                        'totalPlans' => $checkPlans['totalNumberOfPlans'],
                        'hiddenPlans' => $checkPlans['totalNumberOfHiddenPlans'],
                        'visiblePlans' => $checkPlans['totalNumberOfPlans'] - $checkPlans['totalNumberOfHiddenPlans'],
                    ]);

                    $lead->quote_status_id = QuoteStatusEnum::Quoted;
                    PersonalQuote::where('uuid', $lead->uuid)
                        ->where('quote_type_id', QuoteTypeId::Savings)
                        ->update(['quote_status_id' => QuoteStatusEnum::Quoted]);
                    $lead->save();
                } else {
                    LoggerService::info("sendOCAEmail - Quote status is not new lead for quote: {$lead->uuid}");
                }
            }

            LoggerService::info("$logPrefix Bird flow triggered successfully - Email sent to customer", extra: [
                'email' => $emailData->customerEmail,
            ]);

            return $response ?? null;
        } catch (\Exception $e) {
            LoggerService::error("$logPrefix Error triggering event | Message: {$e->getMessage()} Line: {$e->getLine()}");

            return false;
        }
    }

    /**
     * Get the Bird flow URL from Application Storage
     */
    private function getApplicationStorage(): ?string
    {
        return ApplicationStorage::where('key_name', ApplicationStorageEnums::SAVINGS_OCA_EMAIL_FLOW)->value('value');
    }

    /**
     * Map lead data for OCA email
     */
    private function mapOCAEmailData(PersonalQuote $lead, array $data): object
    {
        $firstName = $lead->first_name;
        $lastName = $lead->last_name;
        $customerFullName = trim("{$firstName} {$lastName}");
        $advisor = $lead->advisor;
        $workflowType = WorkflowTypeEnum::SAVINGS_OCA_EMAIL;
        $planIds = isset($data['plan_ids']) && is_array($data['plan_ids'])
            ? implode(',', $data['plan_ids'])
            : ($data['plan_ids'] ?? '');
        $dataSource = config('constants.SAVINGS_EMAIL_DATA_SOURCE', 'IMCRM');
        $ecomQuoteLink = config('constants.ECOM_SAVINGS_INSURANCE_QUOTE_URL', config('constants.WEBSITE_URL').'/savings-insurance/quote/').$lead->uuid;

        return (object) [
            // Lead-related data
            'quoteUID' => $lead->uuid,
            'uuid' => $lead->uuid,
            'customerEmail' => $lead->email,
            'customerFullName' => $customerFullName,
            'customerName' => $customerFullName,
            'refID' => $lead->code,
            'customerMobile' => $lead->mobile_no ?? null,
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::SAVINGS, $lead->uuid),
            'flowExecutedAt' => $lead->automated_flow_executed_at ?? null,

            // Advisor-related data
            'advisorId' => $advisor?->id,
            'advisorName' => $advisor?->name ?? null,
            'advisorEmail' => $advisor?->email ?? null,
            'advisorDetails' => $advisor ?? null,
            'landLine' => $advisor?->landline_no ?? null,
            'mobilePhone' => $advisor?->mobile_no ?? null,
            'whatsAppNumber' => $advisor?->mobile_no ? formatMobileNo($advisor->mobile_no) : null,
            'mobileNoWithoutSpaces' => $advisor?->mobile_no ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : null,
            'planIds' => $planIds,

            // Workflow-related data
            'workflowType' => $workflowType,
            'quotePlanLink' => $ecomQuoteLink,
            'instantAlfredLink' => $ecomQuoteLink.'?IA=true',
            'dataSource' => $dataSource,
        ];
    }

    /**
     * Get the quote by UUID
     */
    private function getQuote(string $quoteUID): ?PersonalQuote
    {
        return PersonalQuote::where([
            'uuid' => $quoteUID,
            'quote_type_id' => QuoteTypeId::Savings,
        ])->with('advisor')->first();
    }

    /**
     * Check plans availability for the quote
     */
    private function checkPlans(string $quoteUID): array
    {
        try {
            $plansData = app(SavingsQuoteService::class)->getAvailablePlans($quoteUID);

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

            if (! $plansData) {
                LoggerService::warning('checkPlans - Invalid or empty plans data structure');

                return [
                    'totalNumberOfHiddenPlans' => 0,
                    'totalNumberOfPlans' => 0,
                    'hasError' => true,
                    'errorMessage' => 'Invalid plans data structure',
                ];
            }

            // Handle different plan structures (regular/lumpsum or flat array)
            $plans = $this->extractPlans($plansData);

            $totalNumberOfPlans = count($plans);
            $totalNumberOfHiddenPlans = 0;

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

    /**
     * Extract plans from the response data handling different structures
     */
    private function extractPlans($plansData): array
    {
        $plans = [];

        if (is_object($plansData)) {
            if (isset($plansData->regular) && is_array($plansData->regular)) {
                $plans = array_merge($plans, $plansData->regular);
            }
            if (isset($plansData->lumpsum) && is_array($plansData->lumpsum)) {
                $plans = array_merge($plans, $plansData->lumpsum);
            }
        }

        if (is_array($plansData)) {
            $plans = $plansData;
        }

        return $plans;
    }
}

