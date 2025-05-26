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
    public function sendOCAEmail(string $quoteUID)
    {

        $logPrefix = 'Life - Send OCA Email';

        $lead = $this->getQuote($quoteUID);

        if (! $lead) {
            LoggerService::info($logPrefix.' - Lead not found');

            return false;
        }

        // check plans, skip the email if the plan is zero
        $plans = $this->getPlans($lead);
        if (count($plans) == 0) {
            LoggerService::info($logPrefix.' - Skipping OCA email on zero plans');

            return false;
        }

        // map data for bird service
        $emailData = $this->mapOCAEmailData($lead, $plans);

        // get bird flow url for Life from ApplicationStorage
        $flowUrl = $this->getApplicationStorage();
        if (! $flowUrl) {
            LoggerService::info($logPrefix.' - Flow URL not found');

            return false;
        }

        try {
            $response = app(BirdService::class)->triggerWebHookRequest($flowUrl, $emailData);

            LoggerService::info('sendLifeOCAEmail - Bird flow triggered successfully');

            return $response ?? null;
        } catch (\Exception $e) {
            LoggerService::info("sendHomeOCBIntroEmail - Error triggering event | Message: {$e->getMessage()} Line: {$e->getLine()}");

            return false;
        }

        LoggerService::info($logPrefix.' - Initiating process');
    }

    private function getPlans(PersonalQuote $quote)
    {
        $quotePlans = app(LifeQuoteService::class)->getQuotePlans($quote->uuid);
        $lifePlans = $quotePlans->quotes->plans;

        return $lifePlans;
    }

    private function attachComparisionPdf(PersonalQuote $quote, $plans)
    {
        $lifePlans = $plans;
        $planIds = collect($lifePlans)->take(5)->pluck('_id')->toArray();
        $pdf = app(LifeQuoteService::class)->exportComparisionPdf($quote, $planIds, $lifePlans);
        $pdfContent = $pdf['pdf']->output();

        LoggerService::info(self::class.' - attachLifeComparisionPdf - Storing PDF temporarily');

        // Generate a unique temporary file path
        $tempFilePath = 'temp/'.uniqid().'.pdf';
        Storage::disk('azureIM')->put($tempFilePath, $pdfContent);

        // Generate a public URL
        $publicUrl = Storage::disk('azureIM')->temporaryUrl(
            $tempFilePath,
            now()->addMinutes(120)
        );
        // Schedule deletion after 5 minutes
        $this->scheduleFileDeletion($tempFilePath);

        LoggerService::info(self::class.' - attachLifeComparisionPdf - Public URL generated');

        return $publicUrl; // Use output() to get raw PDF content

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
    private function mapOCAEmailData($lead, $plans)
    {
        $firstName = $lead->first_name;
        $lastName = $lead->last_name;
        $customerFullName = trim("{$firstName} {$lastName}");
        $advisor = $lead->advisor;
        $workflowType = WorkflowTypeEnum::LIFE_OCA_EMAIL;

        $data = [
            // Lead-related data
            'quoteUID' => $lead->uuid,
            'uuid' => $lead->uuid,
            'customerEmail' => $lead->email,
            'customerFullName' => $customerFullName,
            'customerName' => $customerFullName,
            'refID' => $lead->code,
            'customerMobile' => $lead->mobile_no ?? '',
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::LIFE, $lead->uuid),
            'flowExecutedAt' => $lead->automated_flow_executed_at ?? null,

            // Advisor-related data
            'advisorId' => $advisor?->id,
            'advisorName' => $advisor?->name ?? '',
            'advisorEmail' => $advisor?->email ?? '',
            'advisorDetails' => $advisor ?? null,
            'landLine' => $advisor?->landline_no ?? '',
            'mobilePhone' => $advisor?->mobile_no ?? '',
            'whatsAppNumber' => $advisor?->mobile_no ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => $advisor?->mobile_no ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : '',

            // Workflow-related data
            'workflowType' => $workflowType,
        ];

        $tempUrlPDF = $this->attachComparisionPdf($lead, $plans);

        if (! empty($tempUrlPDF)) {
            $data['pdfLink'] = $tempUrlPDF;
        }

        return (object) $data;
    }

    private function getQuote(string $quoteUID)
    {
        return PersonalQuote::where([
            'uuid' => $quoteUID,
            'quote_type_id' => QuoteTypeId::Life,
        ])->first();
    }
}
