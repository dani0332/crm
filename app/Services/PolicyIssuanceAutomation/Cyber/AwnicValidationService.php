<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\SendPolicyTypeEnum;
use App\Http\Requests\SendBookPolicyRequest;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\Validator;

class AwnicValidationService
{
    private string $className = 'AwnicValidationService';
    public const TYPE = 'Cyber';

    /**
     * Validate book policy prerequisites
     *
     * @param mixed $quote
     * @return array
     */
    public function validateBookPolicy($quote): array
    {
        LoggerService::info('Starting book policy validation', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
        ]);
        $response = ['status' => true, 'error' => null, 'message' => null];

        try {
            $requestData = [
                'quote_id' => $quote->id,
                'model_type' => self::TYPE,
                'send_policy_type' => SendPolicyTypeEnum::SAGE,
                'is_send_policy' => false,
                'transaction_payment_status' => null,
                'through_automation' => true,
            ];
            request()->merge($requestData);

            $sendBookPolicyRequest = new SendBookPolicyRequest();
            $validator = Validator::make($requestData, $sendBookPolicyRequest->rules());
            $sendBookPolicyRequest->withValidator($validator);

            if ($validator->fails()) {
                $response['status'] = false;
                $response['error'] = $validator->errors()->first() ?? 'SendBookPolicyRequest validation failed';
                $response['message'] = $validator->errors()->first();

                LoggerService::error('Validation failed', extra: [
                    'class' => $this->className,
                    'function' => __FUNCTION__,
                    'validation_errors' => $validator->errors()->toArray(),
                    'first_error' => $response['message'],
                ]);

                return $response;
            }

            LoggerService::info('All prerequisites validated successfully', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
            ]);
            $response['message'] = 'All book policy prerequisites validated successfully';
        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = 'Validation error: ' . $e->getMessage();
            $response['message'] = 'An error occurred during validation: ' . $e->getMessage();

            LoggerService::error('Exception during validation', exception: $e);
        }

        return $response;
    }

    /**
     * Validate required data for policy issuance
     *
     * @param mixed $quote
     * @return array
     */
    public function validateRequiredData($quote): array
    {
        $customer = $quote->customer ?? null;
        
        if (!$quote->payments || !$quote->cyberPlanDetail || (!$customer?->emirates_id_number && $customer !== null)) {
            LoggerService::error('Missing required data', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'has_payments' => (bool) $quote->payments,
                'has_plan_detail' => (bool) $quote->cyberPlanDetail,
                'has_emirates_id' => (bool) $customer->emirates_id_number,
            ]);

            return [
                'status' => false,
                'error' => 'Payments or cyber plan detail or emirates id number not found',
                'message' => 'Payments or cyber plan detail or emirates id number not found',
            ];
        }

        return ['status' => true];
    }
}

