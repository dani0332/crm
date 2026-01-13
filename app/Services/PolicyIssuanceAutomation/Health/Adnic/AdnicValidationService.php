<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Http\Requests\SendBookPolicyRequest;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Models\HealthInsurerRequestResponse;

class AdnicValidationService
{
    public function __construct() {}

    /**
     * Validate required data for policy issuance
     *
     * @param  mixed  $quote
     */
    public function validateRequiredData($quote): array
    {
        $customer = $quote->customer;
        $insurerGenerateQuoteRequestResponse = $quote->insurerGenerateQuoteRequestResponse;
        $insurerGenerateQuoteResponse = $insurerGenerateQuoteRequestResponse ? json_decode($insurerGenerateQuoteRequestResponse->response) : null;
        $insurerQuoteNumber = $insurerGenerateQuoteResponse?->QuoteInfo?->QuotationNo;

        $missing = [];

        if (! $quote->healthUmafResponse) {
            $missing[] = 'Health UMAF Response';
        }

        if ($quote->payments->isEmpty()) {
            $missing[] = 'payments';
        }

        if (! $insurerQuoteNumber) {
            $missing[] = 'Insurer Quote Number';
        }
        // Only check emirates id if customer exists (not null)
        if ($customer === null) {
            $missing[] = 'customer';
        }

        if (! empty($missing)) {
            LoggerService::error('Missing required data', extra: [
                'has_payments' => (bool) $quote->payments,
                'has_insurer_quote_number' => (bool) $insurerQuoteNumber,
                'has_customer' => (bool) $customer,
            ]);

            $missingDesc = implode(', ', $missing);

            return [
                'status' => false,
                'error' => "Missing required data: $missingDesc",
                'message' => "Missing required data: $missingDesc",
            ];
        }

        return ['status' => true];
    }

    public function validateUploadDocuments($quote, $quoteDocuments, $insuredInfoDetails): array
    {
        if (! $quoteDocuments || $quoteDocuments->isEmpty()) {
            return [
                'status' => false,
                'error' => 'Required documents not uploaded',
                'message' => 'Required documents not uploaded',
            ];
        }

        if (! $insuredInfoDetails || empty($insuredInfoDetails)) {
            return [
                'status' => false,
                'error' => 'Insured info details not found',
                'message' => 'Insured info details not found',
            ];
        }

        return ['status' => true];
    }

    public function validateDownloadDocuments($quote, $docTypeCodeForIMCRM): array
    {
        $missingDocs = array_keys(array_filter($docTypeCodeForIMCRM, fn ($docId) => $docId === null));
        if (! empty($missingDocs)) {
            return [
                'status' => false,
                'error' => 'Missing documents: '.implode(', ', $missingDocs),
                'message' => 'Missing documents: '.implode(', ', $missingDocs),
            ];
        }

        return [
            'status' => true,
            'error' => null,
            'message' => null,
        ];
    }

}
