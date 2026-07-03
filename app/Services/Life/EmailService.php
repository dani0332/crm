<?php

namespace App\Services\Life;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\DeleteTempOCBPDFFileJob;
use App\Models\ApplicationStorage;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Services\EmailServices\WebEngageService;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Str;

class EmailService
{
    /**
     * Create a new class instance.
     */
    public function sendOCAEmail(string $quoteUID, array $data = [])
    {
        LoggerService::startQuoteLogging($quoteUID);

        $logPrefix = get_class($this).' fn: sendOCAEmail - ';

        LoggerService::info($logPrefix.' - Sending OCA email');

        $lead = $this->getQuote($quoteUID);

        if ($lead->isSuppressIntroEmail()) {
            LoggerService::info('sendOCAEmail - Suppressing OCB Email because for');

            return;
        }

        // map data for  service
        $emailData = $this->mapOCAEmailData($lead, $data);

        try {

            $response = app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::LIFE_OCA_EMAIL, (array) $emailData);

            if ($lead->quote_status_id == QuoteStatusEnum::NewLead) {

                $checkPlans = $this->checkPlans($lead->uuid);

                if (isset($checkPlans['hasError']) && $checkPlans['hasError']) {
                    LoggerService::warning("sendOCAEmail - Error checking plans: {$checkPlans['errorMessage']}, keeping lead status as NewLead");

                    return;
                }

                if ($checkPlans['totalNumberOfPlans'] == 0) {
                    LoggerService::info('sendOCAEmail - total number of plans is 0, so lead status will remain NewLead');

                    return;
                }

                if ($checkPlans['totalNumberOfHiddenPlans'] == $checkPlans['totalNumberOfPlans']) {
                    LoggerService::info('sendOCAEmail - total number of hidden plans is equal to total number of plans, so lead status will remain NewLead');

                    return;
                }

                LoggerService::info('sendOCAEmail - changing lead status to Quoted', [
                    'totalPlans' => $checkPlans['totalNumberOfPlans'],
                    'hiddenPlans' => $checkPlans['totalNumberOfHiddenPlans'],
                    'visiblePlans' => $checkPlans['totalNumberOfPlans'] - $checkPlans['totalNumberOfHiddenPlans'],
                ]);
                if ($lead->quote_status_id == QuoteStatusEnum::NewLead) {
                    $lead->quote_status_id = QuoteStatusEnum::Quoted;
                    LifeQuote::where('uuid', $lead->uuid)->update([
                        'quote_status_id' => QuoteStatusEnum::Quoted,
                    ]);
                    $lead->save();
                }
            } else {
                LoggerService::info("sendOCAEmail - Quote status is not new lead for quote: {$lead->uuid}");
            }

            LoggerService::info("$logPrefix  flow triggered successfully - Email sent to customer", extra: [
                'email' => $emailData->customerEmail,
            ]);

            return $response ?? null;
        } catch (\Exception $e) {
            LoggerService::error("$logPrefix Error triggering event | Message: {$e->getMessage()} Line: {$e->getLine()}");

            return false;
        }
    }

    protected function scheduleFileDeletion($filePath)
    {
        // Use a job to handle file deletion
        DeleteTempOCBPDFFileJob::dispatch($filePath)->delay(now()->addMinutes(120));
    }

    private function getApplicationStorage()
    {
        return ApplicationStorage::where('key_name', ApplicationStorageEnums::LIFE_OCA_EMAIL_FLOW)->value('value');
    }

    private function mapOCAEmailData($lead, $data)
    {
        $firstName = $lead->first_name;
        $lastName = $lead->last_name;
        $customerFullName = trim("{$firstName} {$lastName}");
        $advisor = $lead->advisor;
        $workflowType = WorkflowTypeEnum::LIFE_OCA_EMAIL;
        $planIds = isset($data['plan_ids']) && is_array($data['plan_ids']) ? implode(',', $data['plan_ids']) : ($data['plan_ids'] ?? '');
        $dataSource = config('constants.LIFE_EMAIL_DATA_SOURCE');
        $instantAlfredLink = config('constants.AFIA_WEBSITE_DOMAIN')."/life-insurance/quote/$lead->uuid??IA=true";

        return (object) [
            // Lead-related data
            'quoteUID' => $lead->uuid,
            'uniqueId' => (string) Str::ulid(),
            'uuid' => $lead->uuid,
            'customerEmail' => $lead->email,
            'customerFullName' => $customerFullName,
            'customerName' => $customerFullName,
            'refID' => $lead->code,
            'customerMobile' => $lead->mobile_no ?? null,
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::LIFE, $lead->uuid),
            'flowExecutedAt' => $lead->automated_flow_executed_at ?? null,
            'customerId' => $lead->customer_id ?? '',
            'firstName' => $lead->first_name ?? '',
            'lastName' => $lead->last_name ?? '',

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
            'instantAlfredLink' => $instantAlfredLink,
            'dataSource' => $dataSource,
        ];
    }

    private function getQuote(string $quoteUID): PersonalQuote
    {
        return PersonalQuote::where([
            'uuid' => $quoteUID,
            'quote_type_id' => QuoteTypeId::Life,
        ])->first();
    }

    private function checkPlans(string $quoteUID): array
    {
        try {
            $plansData = app(LifeQuoteService::class)->quotePlans(['quote_uuid' => $quoteUID]);

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
