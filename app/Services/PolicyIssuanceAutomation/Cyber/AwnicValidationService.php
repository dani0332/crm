<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Http\Requests\SendBookPolicyRequest;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\Validator;

class AwnicValidationService
{
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
                'model_type' => QuoteTypes::CYBER->value,
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
        $customer = $quote->customer ?? null;
        $emiratesIdNumber = null;
        LoggerService::info('validateRequiredData for policy issuance - customer details', ['customer' => $quote->customer]);
        if (isset($quote->latestInsured)) {
            LoggerService::info('validateRequiredData for policy issuance - latestInsured details', ['latestInsured' => $quote->latestInsured]);
            $emiratesIdNumber = $quote->latestInsured['id_type'] == 'emiratesId' ? $quote->latestInsured['id_number'] : null;
        }
        $nationality = $quote->nationality ?? null;
        $emirateOfRegistration = $quote?->cyberQuote?->emirateOfRegistration ?? null;

        $missing = [];

        if (! $quote->payments) {
            $missing[] = 'payments';
        }

        if (! $quote->cyberPlanDetail) {
            $missing[] = 'cyber plan detail';
        }

        $nationality == null && $missing[] = 'nationality';
        $emirateOfRegistration == null && $missing[] = 'emirate of registration';

        // Only check emirates id if customer exists (not null)
        if ($customer === null) {
            $missing[] = 'customer';
        } else {
            (empty($emiratesIdNumber) || $emiratesIdNumber == null) && $missing[] = 'emirates id number';
            $customer->dob === null && $missing[] = 'dob';
        }

        if (! empty($missing)) {
            LoggerService::error('Missing required data', extra: [
                'has_payments' => (bool) $quote->payments,
                'has_plan_detail' => (bool) $quote->cyberPlanDetail,
                'has_emirates_id' => (bool) ($emiratesIdNumber),
                'has_nationality' => (bool) ($nationality),
                'has_dob' => (bool) ($customer?->dob),
                'has_emirate_of_registration' => (bool) ($emirateOfRegistration),
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

    public function validateUploadDocuments($quote, $requiredDocuments): array
    {
        if (! $requiredDocuments || empty($requiredDocuments)) {
            return [
                'status' => false,
                'error' => 'Required documents not uploaded',
                'message' => 'Required documents not uploaded',
            ];
        }

        if ($quote->insurer_quote_number == null) {
            return [
                'status' => false,
                'error' => 'Insurer quote number not found',
                'message' => 'Insurer quote number not found',
            ];
        }

        return ['status' => true];
    }

    public function validateDownloadDocuments($docTypeCodeForIMCRM): array
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
