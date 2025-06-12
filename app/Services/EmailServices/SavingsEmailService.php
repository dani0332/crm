<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\QuoteFlowDetails;
use App\Models\SavingsQuote;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;

class SavingsEmailService extends BaseService
{
    public function sendSavingsOCBIntroEmail($lead)
    {
        if (! $lead) {
            LoggerService::info('sendSavingsOCBIntroEmail - Lead not found');

            return false;
        }

        LoggerService::info('sendSavingsOCBIntroEmail - Initiating process');

        // Fetch the advisor
        $advisor = User::find($lead->advisor_id);
        if (! $advisor) {
            LoggerService::info('sendSavingsOCBIntroEmail - Advisor not found');
        }

        // Fetch savings quote
        $savingsQuote = $this->getSavingsQuoteData($lead->uuid);
        if (! $savingsQuote) {
            LoggerService::info('sendSavingsOCBIntroEmail - SavingsQuote not found');

            return false;
        }

        // Build email data
        $emailData = $this->buildEmailData(
            $lead,
            $advisor,
            WorkflowTypeEnum::SAVINGS_OCB,
            $savingsQuote
        );

        // Fetch the OCB workflow configuration
        $savingsOCBEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::SAVINGS_OCB)->first();

        if (! $savingsOCBEvent) {
            LoggerService::info('sendSavingsOCBIntroEmail - OCB workflow configuration not found | Time: '.now());

            return false;
        }

        try {
            $response = app(BirdService::class)->triggerWebHookRequest($savingsOCBEvent->value, $emailData);

            LoggerService::info('sendSavingsOCBIntroEmail - Successfully triggered OCB event');
            if ($response && $response->status_code === 200) {
                $this->createQuoteFlowDetails($lead, $response);
                LoggerService::info('sendSavingsOCBIntroEmail - Quote flow details created for SavingsQuote');
            } else {
                LoggerService::info("sendSavingsOCBIntroEmail - Error triggering event having response status code: {$response?->status_code}");
            }

            return $response ?? null;
        } catch (\Exception $e) {
            LoggerService::info("sendSavingsOCBIntroEmail - Error triggering event | Message: {$e->getMessage()} Line: {$e->getLine()}");

            return false;
        }
    }

    public function buildEmailData($lead, $advisor, $workflowType, $savingsQuote)
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
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::SAVINGS, $lead->uuid),
            // Note: No flow execution tracking needed for OCB-only functionality

            // Savings quote-related data (no automated flow tracking needed for OCB-only)
            'investmentAmount' => $savingsQuote?->investment_amount ?? 0,
            'purpose' => $savingsQuote?->purpose?->text ?? '',
            'tenure' => $savingsQuote?->tenure?->text ?? '',
            'investmentFrequency' => $savingsQuote?->investmentFrequency?->text ?? '',
            'currency' => $savingsQuote?->currency?->code ?? '',

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

        return (object) $data;
    }

    public function getSavingsQuoteData(string $uuid): ?SavingsQuote
    {
        return SavingsQuote::with(['purpose', 'investmentFrequency', 'tenure', 'currency'])
            ->whereHas('personalQuote', function ($query) use ($uuid) {
                $query->where('uuid', $uuid);
            })
            ->first();
    }

    public function createQuoteFlowDetails($lead, $response)
    {
        try {
            $runId = collect($response->headers['Run-Id'])->first();
            if (! empty($runId)) {
                QuoteFlowDetails::create([
                    'quote_uuid' => $lead->uuid,
                    'quote_type_id' => QuoteTypeId::Savings,
                    'flow_type' => QuoteFlowType::SAVINGS_OCB,
                    'flow_id' => $runId,
                ]);
                LoggerService::info(self::class.' SavingsAutomated | workflow run id created');
            } else {
                LoggerService::info(self::class.' SavingsAutomated | workflow run id not found');
            }
        } catch (\Throwable $th) {
            $errorMessage = self::class.' - Error while creating quote flow details';
            LoggerService::info($errorMessage);
            LoggerService::info("Error: {$th->getMessage()}");
            throw $th;
        }
    }
}
