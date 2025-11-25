<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Http\Requests\BookPolicyRequest;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Support\Facades\Validator;

class AwnicBookPolicyService
{
    use GenericQueriesAllLobs;

    private string $className = 'AwnicBookPolicyService';
    public const TYPE = 'Cyber';
    public const BOOK_POLICY = 'BookPolicy';
    public const ISSUE_POLICY = 'IssuePolicy';
    public const UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM = 'UploadPolicyDocumentsToIMCRM';

    public function __construct(
        private AwnicValidationService $validationService,
        private AwnicResponseHandler $responseHandler,
    ) {}

    /**
     * Execute book policy process
     *
     * @param mixed $quote
     * @param mixed $policyIssuance
     * @return array
     */
    public function bookPolicy($quote, $policyIssuance = null): array
    {
        LoggerService::info('Starting book policy process', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'policy_number' => $quote->policy_number,
        ]);

        $response = $this->responseHandler->buildStepResponse(self::BOOK_POLICY);

        $updateBookingDetailsResponse = $this->updateBookingDetails($quote);
        if (! $updateBookingDetailsResponse['status']) {
            LoggerService::error('Update booking details failed', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'error' => $updateBookingDetailsResponse['error'] ?? 'Unknown error',
            ]);

            $response['error'] = $updateBookingDetailsResponse['error'];
            $response['message'] = $updateBookingDetailsResponse['message'];

            return $response;
        }

        $quote->refresh();
        $preCheckResult = $this->validationService->validateBookPolicy($quote);
        if (! $preCheckResult['status']) {
            LoggerService::error('Book policy validation failed', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'error' => $preCheckResult['error'] ?? 'Unknown error',
            ]);

            $response['error'] = $preCheckResult['error'];
            $response['message'] = $preCheckResult['message'];

            return $response;
        }

        $request = new \stdClass;
        $request->quote_id = $quote->id;
        $request->modelType = self::TYPE;
        $request->model_type = self::TYPE;
        $request->is_send_policy = false;
        $request->send_policy_type = SendPolicyTypeEnum::SAGE;
        $request->transaction_payment_status = null;

        LoggerService::info('Creating Sage process', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
        ]);
        $createSageProcessResponse = (new SageApiService)->postBookPolicyToSage($request, $quote);
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, [], $createSageProcessResponse, '', self::BOOK_POLICY, $createSageProcessResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $policyIssuance);

        if (! $createSageProcessResponse['status']) {
            LoggerService::error('Sage process creation failed', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'error' => $createSageProcessResponse['message'] ?? 'Unknown error',
            ]);

            $response['error'] = $createSageProcessResponse['message'];

            return $response;
        }

        LoggerService::info('Book policy process completed successfully', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'sage_message' => $createSageProcessResponse['message'] ?? null,
        ]);

        $response['status'] = true;
        $response['message'] = 'Booking process in started! It will take some time to Complete. Come Back in a while to check the status!';
        $response['completed_step'] = self::BOOK_POLICY;

        return $response;
    }

    /**
     * Update booking details
     *
     * @param mixed $quote
     * @return array
     */
    public function updateBookingDetails($quote): array
    {
        LoggerService::info('Starting update booking details process', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
        ]);
        $response = ['status' => true, 'error' => null, 'message' => null];

        $payment = $quote->payments()->mainLeadPayment()->first();
        $bookPolicyPayload = $this->bookPolicyPayload($quote, QuoteTypes::CYBER->value, $quote->payments, $quote->quoteDocuments);

        LoggerService::info('Booking details prepared', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'invoice_date' => $payment->insurer_invoice_date,
            'insurer_tax_invoice_number' => $payment->insurer_tax_number,
            'commission_vat_applicable' => $payment->commission_vat_applicable,
            'total_commission' => $payment->commission,
            'commission_percentage' => $payment->commmission_percentage,
            'payment_code' => $payment->code,
        ]);

        try {
            $updateBookingRequest = [
                'invoice_date' => $payment->insurer_invoice_date,
                'insurer_tax_invoice_number' => $payment->insurer_tax_number,
                'insurer_commmission_invoice_number' => $payment->insurer_commmission_invoice_number,
                'discount' => $payment->discount_value,
                'transaction_payment_status' => $bookPolicyPayload['transactionPaymentStatus'],
                'broker_invoice_number' => $bookPolicyPayload['brokerInvoiceNo'],
                'commission_vat_not_applicable' => $payment->commission_vat_not_applicable,
                'commission_vat_applicable' => $payment->commission_vat_applicable,
                'total_commission' => $payment->commission,
                'invoice_description' => $bookPolicyPayload['invoiceDescription'],
                'vat_on_commission' => $payment->commission_vat,
                'commission_percentage' => $payment->commmission_percentage,
                'payment_code' => $payment->code,
                'model_type' => self::TYPE,
                'quote_id' => $quote->id,
                'through_automation' => true,
            ];

            request()->merge($updateBookingRequest);

            $bookPolicyRequest = new BookPolicyRequest();
            $validator = Validator::make($updateBookingRequest, $bookPolicyRequest->rules());
            $bookPolicyRequest->withValidator($validator);

            if ($validator->fails()) {
                $response['status'] = false;
                $response['error'] = $validator->errors()->first() ?? 'BookPolicyRequest validation failed';
                $response['message'] = $validator->errors()->first();

                LoggerService::error('Booking details validation failed', extra: [
                    'class' => $this->className,
                    'function' => __FUNCTION__,
                    'validation_errors' => $validator->errors()->toArray(),
                    'first_error' => $response['message'],
                ]);

                return $response;
            }

            LoggerService::info('Validation passed, updating booking details', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
            ]);
            $updateBookingDetailsResponse = app(CentralService::class)->updateBookingDetails($updateBookingRequest, $bookPolicyRequest);

            if (! $updateBookingDetailsResponse['status']) {
                LoggerService::error('Booking details update failed', extra: [
                    'class' => $this->className,
                    'function' => __FUNCTION__,
                    'error' => $updateBookingDetailsResponse['message'] ?? 'Unknown error',
                ]);

                $response['status'] = false;
                $response['error'] = $updateBookingDetailsResponse['message'];
                $response['message'] = $updateBookingDetailsResponse['message'];
            } else {
                LoggerService::info('Booking details updated successfully', extra: [
                    'class' => $this->className,
                    'function' => __FUNCTION__,
                ]);
            }
        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = 'Booking update error: ' . $e->getMessage();
            $response['message'] = 'An error occurred while updating booking details: ' . $e->getMessage();

            LoggerService::error('Exception during booking update', exception: $e);
        }

        return $response;
    }

    /**
     * Get steps locking status for UI
     *
     * @param mixed $quote
     * @param bool $throughAutomation
     * @return array
     */
    public function getStepsLockingStatus($quote, $throughAutomation = false): array
    {
        LoggerService::info('class: ' . $this->className . ' fn: ' . __FUNCTION__ . ' Quote : ' . $quote->code);
        $policyIssuance = $quote->policyIssuance;

        $response = [
            'policyIssuance' => $policyIssuance,
            'isEditPolicyDetailsDisabled' => true,
            'isEditBookingDetailsDisabled' => true,
            'message' => 'All steps are locked',
            'insurer_api_status' => $quote->insurer_api_status,
        ];

        if ($throughAutomation) {
            $response['isEditPolicyDetailsDisabled'] = false;
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = 'All steps are editable';

            return $response;
        }

        if (
            $policyIssuance?->status === PolicyIssuanceEnum::FAILED_STATUS ||
            ($policyIssuance?->completed_step && $policyIssuance?->status == '')
        ) {
            if (! $policyIssuance?->completed_step || $policyIssuance?->completed_step === self::UPLOAD_DOCUMENTS) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'All Steps are editable';

                return $response;
            }
            if ($policyIssuance?->completed_step === self::ISSUE_POLICY) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'Upload Documents and Update Booking Details are editable';

                return $response;
            }
            if ($policyIssuance?->completed_step === self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'Booking Details is editable';

                return $response;
            }

            return $response;
        } elseif (! $policyIssuance) {
            $response['isEditPolicyDetailsDisabled'] = false;
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = 'All Steps are editable';
        }

        if (
            $policyIssuance?->status === PolicyIssuanceEnum::PROCESSING_STATUS &&
            $policyIssuance?->completed_step === self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM
        ) {
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = 'All Steps are editable';

            return $response;
        }

        return $response;
    }
}

