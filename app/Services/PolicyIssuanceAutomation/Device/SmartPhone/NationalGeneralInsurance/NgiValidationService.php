<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Http\Requests\SendBookPolicyRequest;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\Validator;

class NgiValidationService
{
    /**
     * Validate book policy prerequisites
     *
     * @param  mixed  $quote
     */
    public function validateBookPolicy($quote): array
    {
        LoggerService::info('Starting book policy validation for Device/Smartphone');
        $response = ['status' => true, 'error' => null, 'message' => null];

        try {
            $requestData = [
                'quote_id' => $quote->id,
                'model_type' => QuoteTypes::DEVICE->value,
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
    public function validateRequiredData($quote, $customer = null, $deviceQuote = null, $latestInsured = null): array
    {
        $emiratesIdNumber = ($latestInsured?->id_type == 'emiratesId') ? $latestInsured?->id_number : ($customer?->emirates_id_number ?? null);

        $missing = [];

        if (! $quote->payments || $quote->payments->isEmpty()) {
            $missing[] = 'payments';
        }

        if (! $deviceQuote) {
            $missing[] = 'device quote details';
        } else {
            if (empty($deviceQuote->imei)) {
                $missing[] = 'IMEI number';
            }
        }

        if (! $quote->insurer_quote_number) {
            $missing[] = 'insurer quote number';
        }

        if ($customer === null) {
            $missing[] = 'customer';
        } else {
            if (empty($emiratesIdNumber)) {
                $missing[] = 'emirates id number';
            }
        }

        if (! empty($missing)) {
            LoggerService::error('Missing required data', extra: [
                'has_payments' => (bool) ($quote->payments && ! $quote->payments->isEmpty()),
                'has_device_quote' => (bool) $deviceQuote,
                'has_imei' => (bool) ($deviceQuote?->imei),
                'has_insurer_quote_number' => (bool) $quote->insurer_quote_number,
                'has_customer' => (bool) $customer,
                'has_emirates_id' => (bool) ($emiratesIdNumber),
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

    /**
     * Validate policy number exists for document retrieval
     *
     * @param  mixed  $quote
     */
    public function validatePolicyNumberExists($quote): array
    {
        if (empty($quote->policy_number)) {
            return [
                'status' => false,
                'error' => 'Policy number not found. Policy must be issued before fetching documents.',
                'message' => 'Policy number not found. Policy must be issued before fetching documents.',
            ];
        }

        return ['status' => true];
    }

    /**
     * Validate download documents prerequisites
     *
     * @param  mixed  $quote
     */
    public function validateDownloadDocuments(array $documentUrls): array
    {
        $missingDocs = array_keys(array_filter($documentUrls, fn ($url) => $url === null || empty($url)));

        if (! empty($missingDocs)) {
            return [
                'status' => false,
                'error' => 'Missing document URLs: '.implode(', ', $missingDocs),
                'message' => 'Missing document URLs: '.implode(', ', $missingDocs),
            ];
        }

        return [
            'status' => true,
            'error' => null,
            'message' => null,
        ];
    }
}
