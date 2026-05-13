<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\NgiEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Facades\Ngi;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\PolicyIssuanceFailureEmailService;

class NgiApiService
{
    public function __construct(
        private NgiRequestBuilder $requestBuilder,
        private NgiResponseHandler $responseHandler,
        private NgiQuoteUpdaterService $quoteUpdater,
        private NgiValidationService $validationService,
        private PolicyIssuanceFailureEmailService $failureEmailService,
    ) {}

    /**
     * Create policy from quote API call
     *
     * @param  mixed  $quote
     * @param  mixed  $process
     */
    public function createPolicyFromQuote($quote, $process): array
    {
        LoggerService::info('Initiating CreatePolicyFromQuote API call', extra: [
            'process_id' => $process->id,
            'step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
            'policy_start_date' => $quote->policy_start_date,
        ]);

        $response = $this->responseHandler->buildStepResponse(NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE);
        $endPoint = '/api/Policy/CreatePolicyFromQuoteBW';

        $customer = $quote?->customer;
        $deviceQuote = $quote?->deviceQuote;
        $latestInsured = $quote?->latestInsured;

        $payment = $quote?->payments()?->mainLeadPayment()?->first();
        $splitPayment = $payment?->paymentSplits()?->where('payment_method', PaymentMethodsEnum::CreditCard)?->first();

        $payload = $this->requestBuilder->buildCreatePolicyFromQuotePayload($quote, $customer, $deviceQuote, $splitPayment ?? $payment ?? null, $latestInsured);
        $headers = $this->requestBuilder->buildCreatePolicyHeaders();

        $httpResponse = Ngi::post($endPoint, $payload, $headers);
        $createPolicyResponse = $this->responseHandler->parseHttpResponse($httpResponse, NgiEnum::RESPONSE_CREATE_POLICY);

        $this->quoteUpdater->updateQuoteInsurerAndIssuanceStatus($quote, NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE, $createPolicyResponse['status'] ?? false);

        app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
            $quote,
            $payload,
            $httpResponse,
            Ngi::getBaseUrl().$endPoint,
            NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
            $createPolicyResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS,
            $process
        );
        $isPolicyIssuanceFailureEmail = $this->failureEmailService->isPolicyIssuanceFailureEmail($quote->email);
        if (! $createPolicyResponse['status'] || $isPolicyIssuanceFailureEmail) {
            LoggerService::error('CreatePolicyFromQuote API call failed', extra: [
                'endpoint' => $endPoint,
                'error' => $createPolicyResponse['error'] ?? NgiEnum::UNKNOWN_ERROR,
                'message' => $createPolicyResponse['message'] ?? null,
            ]);

            $response['error'] = $createPolicyResponse['error'];
            $response['message'] = $createPolicyResponse['message'];
            $response['status'] = false;

            if ($isPolicyIssuanceFailureEmail) {
                $failureEmailErrorMessage = ' '.sprintf(NgiEnum::FAILURE_EMAIL_DEFAULT_PREFIX_MESSAGE, $quote->email, 'policy issuance');
                $response['error'] .= $failureEmailErrorMessage;
                $response['message'] .= $failureEmailErrorMessage;
            }

            return $response;
        }

        $createPolicyResult = $createPolicyResponse['data'];
        LoggerService::info('CreatePolicyFromQuote API call successful, updating quote', extra: [
            'policy_number' => $createPolicyResult?->policy_no,
            'policy_start_date' => $createPolicyResult?->policy_start_dt,
            'policy_end_date' => $createPolicyResult?->policy_end_dt,
        ]);

        $this->quoteUpdater->updateQuoteFromCreatePolicyResponse($quote, $createPolicyResult);

        $response['status'] = true;
        $response['message'] = 'Policy created successfully';
        $response['completed_step'] = NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE;
        $response['data'] = $createPolicyResult;

        return $response;
    }

    /**
     * Get policy documents API call
     *
     * @param  mixed  $quote
     * @param  mixed  $process
     */
    public function getPolicyDocuments($quote, $process): array
    {
        LoggerService::info('Initiating GetPolicyDocuments API call', extra: [
            'process_id' => $process->id,
            'step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            'policy_number' => $quote->policy_number,
        ]);

        $response = $this->responseHandler->buildStepResponse(NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);

        // Validate policy number exists
        $validationResult = $this->validationService->validatePolicyNumberExists($quote);
        if (! $validationResult['status']) {
            return $validationResult;
        }

        $endPoint = '/api/Policy/GetPolicyDocuments';
        $queryParams = ['policyNumber' => $quote->policy_number];

        $httpResponse = Ngi::get($endPoint, $queryParams);
        $policyDocumentsResponse = $this->responseHandler->parseHttpResponse($httpResponse, NgiEnum::RESPONSE_GET_POLICY_DOCUMENTS);

        $this->quoteUpdater->updateQuoteInsurerAndIssuanceStatus($quote, NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, $policyDocumentsResponse['status'] ?? false);

        app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
            $quote,
            $queryParams,
            $httpResponse,
            Ngi::getBaseUrl().$endPoint.'?'.http_build_query($queryParams),
            NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            $policyDocumentsResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS,
            $process
        );
        $isDocumentDownloadFailureEmail = $this->failureEmailService->isDocumentDownloadFailureEmail($quote->email);
        if (! $policyDocumentsResponse['status'] || $isDocumentDownloadFailureEmail) {
            LoggerService::error('GetPolicyDocuments API call failed', extra: [
                'endpoint' => $endPoint,
                'policy_number' => $quote->policy_number,
                'error' => $policyDocumentsResponse['error'] ?? NgiEnum::UNKNOWN_ERROR,
                'message' => $policyDocumentsResponse['message'] ?? null,
            ]);

            $response['error'] = $policyDocumentsResponse['error'];
            $response['message'] = $policyDocumentsResponse['message'];
            $response['status'] = false;

            return $response;
        }

        $policyDocumentsResult = $policyDocumentsResponse['data'];
        LoggerService::info('GetPolicyDocuments API call successful, updating quote and payment', extra: (array) $policyDocumentsResult);

        $this->quoteUpdater->updateQuoteFromPolicyDocumentsResponse($quote, $policyDocumentsResult);
        $this->quoteUpdater->updatePaymentFromPolicyDocumentsResponse($quote->code, $policyDocumentsResult);

        $response['status'] = true;
        $response['message'] = 'Policy documents retrieved successfully';
        $response['completed_step'] = NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM;
        $response['data'] = $policyDocumentsResult;

        return $response;
    }

}
