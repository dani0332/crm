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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\RenewalsBatchEmails;
use App\Models\RenewalQuoteProcess;
use App\Services\HomeQuoteService;
use Illuminate\Support\Facades\Storage;

class HomeEmailService extends BaseService
{
    public function sendHomeOCBIntroEmail($lead)
    {
        if (! $lead) {
            info('sendHomeOCBIntroEmail - Lead not found | Time: '.now());

            return false;
        }

        info('sendHomeOCBIntroEmail - Initiating process | Time: '.now());

        // Fetch the advisor
        $advisor = User::find($lead->advisor_id);
        if (! $advisor) {
            info('sendHomeOCBIntroEmail - Advisor not found | Time: '.now());
        }

        // Fetch home quote
        $homeQuote = $this->getHomeQuoteData($lead->uuid);
        if (! $homeQuote) {
            info('sendHomeOCBIntroEmail - HomeQuote not found | Time: '.now());

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
            info('sendHomeOCBIntroEmail - Workflow configuration not found | Time: '.now());

            return false;
        }

        try {
            $response = app(BirdService::class)->triggerWebHookRequest($homeAutomatedEvent->value, $emailData);

            if (empty($homeQuote->automated_flow_executed_at)) {
                $homeQuote->automated_flow_executed_at = now();
                $homeQuote->save();
                info('sendHomeOCBIntroEmail - Automated flow timestamp updated for HomeQuote | Time: '.now());
                info('sendHomeOCBIntroEmail - Successfully triggered event | Time: '.now());
                if ($response && $response->status_code === 200) {
                    $this->createQuoteFlowDetails($lead, $response);
                    info('sendHomeOCBIntroEmail - Quote flow details created for HomeQuote | Time: '.now());
                } else {
                    info("sendHomeOCBIntroEmail - Error triggering event having response status code: {$response?->status_code}");
                }
            }

            return $response ?? null;
        } catch (\Exception $e) {
            info("sendHomeOCBIntroEmail - Error triggering event | Message: {$e->getMessage()} Line: {$e->getLine()} | Time: ".now());

            return false;
        }
    }

    public function sendRenewalOCBEmail($batch, RenewalsBatchEmails $renewalsBatchEmail, RenewalQuoteProcess $renewalQuoteProcess)
    {
        try {
            // Find Home Quote
            $lead = HomeQuote::find($renewalQuoteProcess->quote_id);
            
            info('Home Renewals OCB Email started for uuid: '.$lead->uuid);

            // Get Lead Advisor
            $advisor = User::where('id', $lead->advisor_id)->first();

            // Map Data for Home OCB Email
            $emailData = $this->mapDataForRenewalOCBEmail($lead, $advisor, WorkflowTypeEnum::HOME_RENEWAL_OCB);

            $workflowUrl = ApplicationStorage::where('key_name', WorkflowTypeEnum::HOME_RENEWAL_OCB)->first()?->value;
            
            if($workflowUrl){
                app(BirdService::class)->triggerWebHookRequest($workflowUrl, $emailData);
                info('Renewals OCB Email completed for uuid: '.$lead->uuid);

                RenewalsBatchEmails::where('id', $renewalsBatchEmail->id)->update(['total_sent' => DB::raw('total_sent+1')]);
                RenewalQuoteProcess::where('id', $renewalQuoteProcess->id)->update(['email_sent' => 1]);

            }else{
                info('Home Renewals OCB Email failed error: Workflow URL not found');
            }
            
        } catch (\Exception $exception) {
            Log::info('Renewals OCB Email failed error: '.$exception->getMessage());
            RenewalsBatchEmails::where('id', $renewalsBatchEmail->id)->update(['total_failed' => DB::raw('total_failed+1')]);
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
            'customerMobile' => (! empty($lead->mobile_no) ? $lead->mobile_no : ''),
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::HOME, uuid: $lead->uuid),
        ];
    }

    private function mapDataForRenewalOCBEmail($lead, $advisor, $workflowType)
    {
        return (object) [
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'refID' => $lead->code,
            'automatedFlowExecuted' => empty($lead->automated_flow_executed_at) ? true : false,
            'uuid' => $lead->uuid,
            'customerFullName' => "{$lead->first_name} {$lead->last_name}",
            'customerName' => "{$lead->first_name} {$lead->last_name}",
            'advisorId' => $advisor?->id ?? null,
            'advisorName' => $advisor?->name ?? '',
            'advisorEmail' => $advisor?->email ?? '',
            'advisorDetails' => $advisor ?? null,
            'flowExecutedAt' => $lead->flow_executed_at ?? null,
            'landLine' => (! empty($advisor?->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor?->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor?->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor?->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'workflowType' => $workflowType,
            'customerMobile' => (! empty($lead->mobile_no) ? $lead->mobile_no : ''),
            // 'whatsappConsent' => getWhatsappConsent(QuoteTypes::HOME, uuid: $lead->uuid),
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
            info(self::class.' - attachHomeOCBPDFToEmail - Generating PDF | Time: '.now());

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
                info(self::class.' - attachHomeOCBPDFToEmail - No plans found | Time: '.now());

                return '';
            }

            $pdfFile = app(HomeQuoteService::class)->exportPlansPdf(QuoteTypes::HOME->value, ['quote_uuid' => $quoteUID, 'plan_ids' => $planIds]);
            $pdfContent = $pdfFile['pdf']->output(); // Use output() to get raw PDF content

            info(self::class.' - attachHomeOCBPDFToEmail - Storing PDF temporarily | Time: '.now());

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

            info(self::class.' - attachHomeOCBPDFToEmail - Public URL generated | Time: '.now());

            return $publicUrl;
        } catch (\Exception $e) {
            // Log the error details
            info(self::class." - Error: attachHomeOCBPDFToEmail - Error attaching PDF | Message: {$e->getMessage()} | File: {$e->getFile()} | Line: {$e->getLine()} | Time: ".now());

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
                info(self::class.' HomeAutomated | workflow run id created |Time: '.now());
            } else {
                info(self::class.' HomeAutomated | workflow run id not found |Time: '.now());
            }
        } catch (\Throwable $th) {
            $errorMessage = self::class.' - Error while creating quote flow details | Time: '.now();
            info($errorMessage);
            info("Error: {$th->getMessage()} | Time: ".now());
            throw $th;
        }
    }

    public function updateHomeAutomatedFlowExecuted(string $quoteUID): void
    {
        try {
            $homeQuote = HomeQuote::where('uuid', $quoteUID)->first();

            if (! $homeQuote) {
                logger()->warning('HomeQuote not found for Quote UID.', [
                    'quoteUID' => $quoteUID,
                    'action' => 'updateHomeAutomatedFlowExecuted',
                ]);

                return;
            }

            $homeQuote->automated_flow_executed_at = null;
            $homeQuote->save();

            logger()->info('Automated flow timestamp updated for HomeQuote.', [
                'quoteUID' => $quoteUID,
                'refId' => $homeQuote->uuid,
                'action' => 'updateHomeAutomatedFlowExecuted',
            ]);
        } catch (\Exception $e) {
            logger()->error('Failed to update automated flow timestamp for HomeQuote.', [
                'quoteUID' => $quoteUID,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'action' => 'updateHomeAutomatedFlowExecuted',
            ]);
        }
    }
}
