<?php

namespace App\Services\Life;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\DeleteTempOCBPDFFileJob;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\Storage;

class EmailService
{
    /**
     * Create a new class instance.
     */
    public function sendOCAEmail(string $quoteUID, array $data = [])
    {
        LoggerService::startQuoteLogging($quoteUID);

        $logPrefix = get_class($this). ' fn: sendOCAEmail - ';

        LoggerService::info($logPrefix.' - Sending OCA email');

        $lead = $this->getQuote($quoteUID);

        // map data for bird service
        $emailData = $this->mapOCAEmailData($lead, $data);

        // get bird flow url for Life from ApplicationStorage
        $flowUrl = $this->getApplicationStorage();
        if (! $flowUrl) {
            LoggerService::info($logPrefix.' - Flow URL not found', );
            return false;
        }

        try {

            $response = app(BirdService::class)->triggerWebHookRequest($flowUrl, $emailData);
            
            LoggerService::info("$logPrefix Bird flow triggered successfully - Email sent to customer", extra: [
                'email' => $emailData->customerEmail,
            ]);

            return $response ?? null;
        } catch (\Exception $e) {
            LoggerService::error("$logPrefix Error triggering event | Message: {$e->getMessage()} Line: {$e->getLine()}");
            return false;
        }
    }

    private function getPlans(PersonalQuote $quote)
    {
        $quotePlans = app(LifeQuoteService::class)->getQuotePlans($quote->uuid);
        $lifePlans = $quotePlans->quotes->plans;

        return $lifePlans;
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

        return (object) [
            // Lead-related data
            'quoteUID' => $lead->uuid,
            'uuid' => $lead->uuid,
            'customerEmail' => $lead->email,
            'customerFullName' => $customerFullName,
            'customerName' => $customerFullName,
            'refID' => $lead->code,
            'customerMobile' => $lead->mobile_no ?? null,
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::LIFE, $lead->uuid),
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

            'planIds' => $data['plan_ids'] ?? null,
            // Workflow-related data
            'workflowType' => $workflowType,
        ];
    }

    private function getQuote(string $quoteUID): PersonalQuote
    {
        return PersonalQuote::where([
            'uuid' => $quoteUID,
            'quote_type_id' => QuoteTypeId::Life,
        ])->first();
    }
}
