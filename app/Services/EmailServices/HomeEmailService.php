<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\DeleteTempOCBPDFFileJob;
use App\Models\ApplicationStorage;
use App\Models\HomeQuote;
use App\Models\QuoteFlowDetails;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\HomeQuoteService;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\Storage;

class HomeEmailService extends BaseService
{
    public function sendHomeOCBIntroEmail($lead)
    {
        if (! $lead) {
            LoggerService::info('sendHomeOCBIntroEmail - Lead not found');

            return false;
        }

        LoggerService::info('sendHomeOCBIntroEmail - Initiating process');

        // Fetch the advisor
        $advisor = User::find($lead->advisor_id);
        if (! $advisor) {
            LoggerService::info('sendHomeOCBIntroEmail - Advisor not found');
        }

        // Fetch home quote
        $homeQuote = $this->getHomeQuoteData($lead->uuid);
        if (! $homeQuote) {
            LoggerService::info('sendHomeOCBIntroEmail - HomeQuote not found');

            return false;
        }

        // Build email data
        $emailData = $this->buildEmailData(
            $lead,
            $advisor,
            WorkflowTypeEnum::HOME_AUTOMATED_FOLLOWUPS,
            $homeQuote
        );

        // Fetch the automated workflow configuration
        $homeAutomatedEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::HOME_OCB_AUTOMATED_FOLLOWUPS)->first();

        if (! $homeAutomatedEvent) {
            LoggerService::info('sendHomeOCBIntroEmail - Workflow configuration not found | Time: '.now());

            return false;
        }

        try {
            $response = app(BirdService::class)->triggerWebHookRequest($homeAutomatedEvent->value, $emailData);

            if (empty($homeQuote->automated_flow_executed_at)) {
                $homeQuote->automated_flow_executed_at = now();
                $homeQuote->save();
                LoggerService::info('sendHomeOCBIntroEmail - Automated flow timestamp updated for HomeQuote');
                LoggerService::info('sendHomeOCBIntroEmail - Successfully triggered event');
                if ($response && $response->status_code === 200) {
                    $this->createQuoteFlowDetails($lead, $response);
                    LoggerService::info('sendHomeOCBIntroEmail - Quote flow details created for HomeQuote');
                } else {
                    LoggerService::info("sendHomeOCBIntroEmail - Error triggering event having response status code: {$response?->status_code}");
                }
            }

            return $response ?? null;
        } catch (\Exception $e) {
            LoggerService::info("sendHomeOCBIntroEmail - Error triggering event | Message: {$e->getMessage()} Line: {$e->getLine()}");

            return false;
        }
    }

    public function buildEmailData($lead, $advisor, $workflowType, $homeQuote)
    {
        $data = [
            // Lead-related data
            'quoteUID' => $lead->uuid,
            'uuid' => $lead->uuid,
            'customerEmail' => $lead->email,
            'customerFullName' => trim("{$lead->first_name} {$lead->last_name}"),
            'customerName' => trim("{$lead->first_name} {$lead->last_name}"),
            'refID' => $lead->code,
            'customerMobile' => $lead->mobile_no ?? '',
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::HOME, $lead->uuid),
            'flowExecutedAt' => $lead->automated_flow_executed_at ?? null,

            // Home quote-related data
            'automatedFlowExecuted' => ! empty($homeQuote?->automated_flow_executed_at),

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

        $tempUrlPDF = $this->attachHomeOCBPDFToEmail($lead->uuid);

        if (! empty($tempUrlPDF)) {
            $data['tempUrlPDF'] = $tempUrlPDF;
        }

        return (object) $data;
    }

    public function getHomeQuoteData(string $uuid): ?HomeQuote
    {
        return HomeQuote::with('subArea:id,text')
            ->where('uuid', $uuid)
            ->first();
    }

    public function attachHomeOCBPDFToEmail($quoteUID)
    {
        try {
            LoggerService::info(self::class.' - attachHomeOCBPDFToEmail - Generating PDF');

            $quotePlans = app(HomeQuoteService::class)->getQuotePlans($quoteUID);

            // Generate the PDF
            $planIds = [];
            if (isset($quotePlans->quotes->plans)) {
                $planIds = collect($quotePlans->quotes->plans)
                    ->filter(function ($plan) {
                        return ! $plan->isDisabled && $plan->isRatingAvailable;
                    })
                    ->sortByDesc('isRenewal')
                    ->pluck('id')
                    ->take(5)
                    ->toArray() ?? [];
            }

            if (empty($planIds)) {
                LoggerService::info(self::class.' - attachHomeOCBPDFToEmail - No plans found');

                return '';
            }

            $pdfFile = app(HomeQuoteService::class)->exportPlansPdf(QuoteTypes::HOME->value, ['quote_uuid' => $quoteUID, 'plan_ids' => $planIds]);
            $pdfContent = $pdfFile['pdf']->output(); // Use output() to get raw PDF content

            LoggerService::info(self::class.' - attachHomeOCBPDFToEmail - Storing PDF temporarily');

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

            LoggerService::info(self::class.' - attachHomeOCBPDFToEmail - Public URL generated');

            return $publicUrl;
        } catch (\Exception $e) {
            // Log the error details
            LoggerService::info(self::class." - Error: attachHomeOCBPDFToEmail - Error attaching PDF | Message: {$e->getMessage()} | File: {$e->getFile()} | Line: {$e->getLine()}");

            return false;
        }
    }
    protected function scheduleFileDeletion($filePath)
    {
        // Use a job to handle file deletion
        DeleteTempOCBPDFFileJob::dispatch($filePath)->delay(now()->addMinutes(120));
    }

    public function createQuoteFlowDetails($lead, $response)
    {
        try {
            $runId = collect($response->headers['Run-Id'])->first();
            if (! empty($runId)) {
                QuoteFlowDetails::create([
                    'quote_uuid' => $lead->uuid,
                    'quote_type_id' => QuoteTypeId::Home,
                    'flow_type' => QuoteFlowType::HOME_AUTOMATED_FOLLOWUPS,
                    'flow_id' => $runId,
                ]);
                LoggerService::info(self::class.' HomeAutomated | workflow run id created');
            } else {
                LoggerService::info(self::class.' HomeAutomated | workflow run id not found');
            }
        } catch (\Throwable $th) {
            $errorMessage = self::class.' - Error while creating quote flow details';
            LoggerService::info($errorMessage);
            LoggerService::info("Error: {$th->getMessage()}");
            throw $th;
        }
    }
}
