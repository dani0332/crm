<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteStatusEnum;
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
use Illuminate\Support\Facades\Storage;

class HomeEmailService extends BaseService
{
    public function sendHomeOCBIntroEmail($lead)
    {
        if (! $lead) {
            info('sendHomeOCBIntroEmail - Lead not found | Time: ' . now());

            return false;
        }

        info("sendHomeOCBIntroEmail - Initiating process for Lead Ref ID: {$lead->uuid} | Time: " . now());

        // Fetch the advisor
        $advisor = User::find($lead->advisor_id);
        if (! $advisor) {
            info("sendHomeOCBIntroEmail - Advisor not found for Lead Ref ID: {$lead->uuid} | Time: " . now());
        }

        // Fetch home quote
        $homeQuote = $this->getHomeQuoteData($lead->uuid);
        if (! $homeQuote) {
            info("sendHomeOCBIntroEmail - HomeQuote not found for Lead Ref ID: {$lead->uuid} | Time: " . now());

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
            info("sendHomeOCBIntroEmail - Workflow configuration not found for Lead Ref ID: {$lead->uuid} | Time: " . now());

            return false;
        }

        try {
            $response = app(BirdService::class)->triggerWebHookRequest($homeAutomatedEvent->value, $emailData);

            if (empty($homeQuote->automated_flow_executed_at)) {
                $homeQuote->automated_flow_executed_at = now();
                $homeQuote->quote_status_id = QuoteStatusEnum::Quoted;
                $homeQuote->save();
                $lead->quote_status_id = QuoteStatusEnum::Quoted;
                $lead->save();
                info("sendHomeOCBIntroEmail - Automated flow timestamp updated for HomeQuote Ref-ID: {$homeQuote->id} | Time: " . now());
                info("sendHomeOCBIntroEmail - Successfully triggered event for Lead Ref ID: {$lead->uuid} | Time: " . now());
                if (!empty($response->headers['Run-Id'])) {
                    $this->createQuoteFlowDetails($lead, $response);
                    // info("sendHomeOCBIntroEmail - Run-ID: " . json_encode($response->headers['Run-Id']) . " for HomeQuote Ref-ID: {$homeQuote->id} | Time: " . now());
                    info("sendHomeOCBIntroEmail - Run Id case executed for HomeQuote Ref-ID: {$homeQuote->id} | Time: " . now());
                }
            }

            return $response ?? null;
        } catch (\Exception $e) {
            info("sendHomeOCBIntroEmail - Error triggering event for Lead Ref ID: {$lead->uuid} | Message: {$e->getMessage()} Line: {$e->getLine()} | Time: " . now());

            return false;
        }
    }

    public function buildEmailData($lead, $advisor, $workflowType, $homeQuote)
    {
        return (object) [
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
            'tempUrlPDF' => $this->attachHomeOCBPDFToEmail($lead->uuid),
        ];
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
            info(self::class . " - attachHomeOCBPDFToEmail - Generating PDF for Quote UID: {$quoteUID} | Time: " . now());
            $quotePlans = app(HomeQuoteService::class)->getQuotePlans($quoteUID);
            // Generate the PDF
            $planIds = [];
            if (isset($quotePlans->quotes->plans)) {
                $planIds = collect($quotePlans->quotes->plans)->pluck('id')->take(5)->toArray() ?? [];
            }

            $pdfFile = app(HomeQuoteService::class)->exportPlansPdf(QuoteTypes::HOME->value, ['quote_uuid' => $quoteUID, 'plan_ids' => $planIds]);
            $pdfContent = $pdfFile['pdf']->output(); // Use output() to get raw PDF content

            info(self::class . " - attachHomeOCBPDFToEmail - Storing PDF temporarily for Quote UID: {$quoteUID} | Time: " . now());

            // Generate a unique temporary file path
            $tempFilePath = 'temp/' . uniqid() . '.pdf';
            Storage::disk('azureIM')->put($tempFilePath, $pdfContent);

            // Generate a public URL
            // $publicUrl = asset('storage/' . $tempFilePath);
            $publicUrl = Storage::disk('azureIM')->temporaryUrl(
                $tempFilePath,
                now()->addMinutes(120)
            );
            // Schedule deletion after 5 minutes
            $this->scheduleFileDeletion($tempFilePath);

            info(self::class . " - attachHomeOCBPDFToEmail - Public URL generated for Quote UID: {$quoteUID} | Time: " . now());

            return $publicUrl;
        } catch (\Exception $e) {
            // Log the error details
            info(self::class . " - Error: attachHomeOCBPDFToEmail - Error attaching PDF for Quote UID: {$quoteUID} | Message: {$e->getMessage()} | File: {$e->getFile()} | Line: {$e->getLine()} | Time: " . now());

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
                info(self::class . " HomeAutomated | workflow run id created for lead : Ref-ID: {$lead->uuid} |Time: " . now());
            } else {
                info(self::class . " HomeAutomated | workflow run id not found for lead : Ref-ID: {$lead->uuid} |Time: " . now());
            }
        } catch (\Throwable $th) {
            $errorMessage = self::class . " - Error while creating quote flow details for lead: Ref-ID: {$lead->uuid} | Time: " . now();
            info($errorMessage);
            info("Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: " . now());
            throw $th;
        }
    }
}
