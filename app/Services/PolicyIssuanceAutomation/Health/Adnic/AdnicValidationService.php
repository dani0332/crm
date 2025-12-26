<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Http\Requests\SendBookPolicyRequest;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\Validator;

class AdnicValidationService
{
    public function __construct(
        private AdnicDocumentHandler $documentHandler,
    ) {}

    /**
     * Validate book policy prerequisites
     *
     * @param  mixed  $quote
     */
    public function validateBookPolicy($quote): array
    {
        LoggerService::info('Starting book policy validation');
        $response = ['status' => true, 'error' => null, 'message' => null];

        try {
            $requestData = [
                'quote_id' => $quote->id,
                'model_type' => QuoteTypes::HEALTH->value,
                'send_policy_type' => SendPolicyTypeEnum::SAGE,
                'is_send_policy' => false,
                'transaction_payment_status' => null,
                'through_automation' => true,
            ];
            request()->merge($requestData);

            $sendBookPolicyRequest = new SendBookPolicyRequest;
            $validator = Validator::make($requestData, $sendBookPolicyRequest->rules());
            $sendBookPolicyRequest->withValidator($validator);

            if ($validator->fails()) {
                $response['status'] = false;
                $response['error'] = $validator->errors()->first() ?? 'SendBookPolicyRequest validation failed';
                $response['message'] = $validator->errors()->first();

                LoggerService::error('Validation failed', extra: [
                    'validation_errors' => $validator->errors()->toArray(),
                    'first_error' => $response['message'],
                ]);

                return $response;
            }

            LoggerService::info('All prerequisites validated successfully');
            $response['message'] = 'All book policy prerequisites validated successfully';
        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = 'Validation error: '.$e->getMessage();
            $response['message'] = 'An error occurred during validation: '.$e->getMessage();

            LoggerService::error('Exception during validation', exception: $e);
        }

        return $response;
    }

    /**
     * Validate required data for policy issuance
     *
     * @param  mixed  $quote
     */
    public function validateRequiredData($quote): array
    {
        $customer = $quote->customer;
        $nationality = $quote->nationality;
        $insurerGenerateQuoteRequestResponse = $quote->insurerGenerateQuoteRequestResponse;
        $insurerGenerateQuoteResponse = $insurerGenerateQuoteRequestResponse ? json_decode($insurerGenerateQuoteRequestResponse->response) : null;
        $insurerQuoteNumber = $insurerGenerateQuoteResponse?->QuoteInfo?->QuotationNo;

        $missing = [];

        if (! $quote->payments) {
            $missing[] = 'payments';
        }

        if (! $insurerQuoteNumber) {
            $missing[] = 'insurer quote number';
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
