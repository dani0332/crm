<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\AwnicEnum;
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

    public function __construct(
        private AwnicValidationService $validationService,
        private AwnicResponseHandler $responseHandler,
    ) {}

    /**
     * Execute book policy process
     *
     * @param  mixed  $quote
     * @param  mixed  $policyIssuance
     */
    public function bookPolicy($quote, $policyIssuance = null): array
    {
        LoggerService::info('Starting book policy process', extra: [
            'policy_number' => $quote->policy_number,
        ]);

        $response = $this->responseHandler->buildStepResponse(AwnicEnum::STEP_BOOK_POLICY);

        // Step control
        $processFailed = false;

        // Step 1: Update booking details
        $updateBookingDetailsResponse = $this->updateBookingDetails($quote);
        if (! $updateBookingDetailsResponse['status']) {
            LoggerService::error('Update booking details failed', extra: [
                'error' => $updateBookingDetailsResponse['error'] ?? AwnicEnum::UNKNOWN_ERROR,
            ]);

            $response['error'] = $updateBookingDetailsResponse['error'];
            $response['message'] = $updateBookingDetailsResponse['message'];
            $processFailed = true;
        }

        // Step 2: Pre-check validation (only if previous step succeeded)
        if (! $processFailed) {
            $quote->refresh();
            $preCheckResult = $this->validationService->validateBookPolicy($quote);
            if (! $preCheckResult['status']) {
                LoggerService::error('Book policy validation failed', extra: [
                    'error' => $preCheckResult['error'] ?? AwnicEnum::UNKNOWN_ERROR,
                ]);

                $response['error'] = $preCheckResult['error'];
                $response['message'] = $preCheckResult['message'];
                $processFailed = true;
            }
        }

        // Step 3: Create Sage process (only if previous steps succeeded)
        if (! $processFailed) {
            $request = new \stdClass;
            $request->quote_id = $quote->id;
            $request->modelType = QuoteTypes::CYBER->value;
            $request->model_type = QuoteTypes::CYBER->value;
            $request->is_send_policy = false;
            $request->send_policy_type = SendPolicyTypeEnum::SAGE;
            $request->transaction_payment_status = null;

            LoggerService::info('Creating Sage process');
            $createSageProcessResponse = (new SageApiService)->postBookPolicyToSage($request, $quote);
            app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
                $quote,
                [],
                $createSageProcessResponse,
                '',
                AwnicEnum::STEP_BOOK_POLICY,
                $createSageProcessResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS,
                $policyIssuance
            );

            if (! $createSageProcessResponse['status']) {
                LoggerService::error('Sage process creation failed', extra: [
                    'error' => $createSageProcessResponse['message'] ?? AwnicEnum::UNKNOWN_ERROR,
                ]);

                $response['error'] = $createSageProcessResponse['message'];
                $processFailed = true;
            } else {
                LoggerService::info('Book policy process completed successfully', extra: [
                    'sage_message' => $createSageProcessResponse['message'] ?? null,
                ]);
                $response['status'] = true;
                $response['message'] = 'Booking process in started! It will take some time to Complete. Come Back in a while to check the status!';
                $response['completed_step'] = AwnicEnum::STEP_BOOK_POLICY;
            }
        }

        return $response;
    }

    /**
     * Update booking details
     *
     * @param  mixed  $quote
     */
    public function updateBookingDetails($quote): array
    {
        LoggerService::info('Starting update booking details process');
        $response = ['status' => true, 'error' => null, 'message' => null];

        $payment = $quote->payments()->mainLeadPayment()->first();
        $bookPolicyPayload = $this->bookPolicyPayload($quote, QuoteTypes::CYBER->value, $quote->payments, $quote->quoteDocuments);

        LoggerService::info('Booking details prepared', extra: [
            'invoice_date' => $payment?->insurer_invoice_date,
            'insurer_tax_invoice_number' => $payment?->insurer_tax_number,
            'commission_vat_applicable' => $payment?->commission_vat_applicable,
            'total_commission' => $payment?->commission,
            'commission_percentage' => $payment?->commmission_percentage,
            'payment_code' => $payment?->code,
        ]);

        try {
            $updateBookingRequest = [
                'invoice_date' => $payment?->insurer_invoice_date,
                'insurer_tax_invoice_number' => $payment?->insurer_tax_number,
                'insurer_commmission_invoice_number' => $payment?->insurer_commmission_invoice_number,
                'discount' => $payment?->discount_value,
                'transaction_payment_status' => $bookPolicyPayload['transactionPaymentStatus'],
                'broker_invoice_number' => $bookPolicyPayload['brokerInvoiceNo'],
                'commission_vat_not_applicable' => $payment?->commission_vat_not_applicable,
                'commission_vat_applicable' => $payment?->commission_vat_applicable,
                'total_commission' => $payment?->commission,
                'invoice_description' => $bookPolicyPayload['invoiceDescription'],
                'vat_on_commission' => $payment?->commission_vat,
                'commission_percentage' => $payment?->commmission_percentage,
                'payment_code' => $payment?->code,
                'model_type' => QuoteTypes::CYBER->value,
                'quote_id' => $quote?->id,
                'through_automation' => true,
            ];

            request()->merge($updateBookingRequest);

            $bookPolicyRequest = new BookPolicyRequest;
            $validator = Validator::make($updateBookingRequest, $bookPolicyRequest->rules());
            $bookPolicyRequest->withValidator($validator);

            if ($validator->fails()) {
                $response['status'] = false;
                $response['error'] = $validator->errors()->first() ?? 'BookPolicyRequest validation failed';
                $response['message'] = $validator->errors()->first();

                LoggerService::error('Booking details validation failed', extra: [
                    'validation_errors' => $validator->errors()->toArray(),
                    'first_error' => $response['message'],
                ]);

                return $response;
            }

            LoggerService::info('Validation passed, updating booking details');
            $updateBookingDetailsResponse = app(CentralService::class)->updateBookingDetails($updateBookingRequest, $bookPolicyRequest);

            if (! $updateBookingDetailsResponse['status']) {
                LoggerService::error('Booking details update failed', extra: [
                    'error' => $updateBookingDetailsResponse['message'] ?? AwnicEnum::UNKNOWN_ERROR,
                ]);

                $response['status'] = false;
                $response['error'] = $updateBookingDetailsResponse['message'];
                $response['message'] = $updateBookingDetailsResponse['message'];
            } else {
                LoggerService::info('Booking details updated successfully');
            }
        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = 'Booking update error: '.$e->getMessage();
            $response['message'] = 'An error occurred while updating booking details: '.$e->getMessage();

            LoggerService::error('Exception during booking update', exception: $e);
        }

        return $response;
    }

    /**
     * Get steps locking status for UI
     *
     * @param  mixed  $quote
     * @param  bool  $throughAutomation
     */
    public function getStepsLockingStatus($quote, $throughAutomation = false): array
    {
        LoggerService::info('Getting steps locking status for Cyber quote', extra: [
            'quote_id' => $quote->id,
            'quote_type' => QuoteTypes::CYBER->value,
            'quote_code' => $quote->code,
        ]);
        $policyIssuance = $quote->policyIssuance;

        $response = [
            'policyIssuance' => $policyIssuance,
            'isEditPolicyDetailsDisabled' => true,
            'isEditBookingDetailsDisabled' => true,
            'message' => 'All steps are locked',
            'insurer_api_status' => $quote->insurer_api_status,
        ];

        // Early exit (first return)
        if ($throughAutomation) {
            $response['isEditPolicyDetailsDisabled'] = false;
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = AwnicEnum::ALL_STEPS_ARE_EDITABLE;

            return $response;
        }

        $shouldHandlePolicyIssuanceLogic = (
            $policyIssuance?->status === PolicyIssuanceEnum::FAILED_STATUS ||
            ($policyIssuance?->completed_step && $policyIssuance?->status == '')
        );

        if ($shouldHandlePolicyIssuanceLogic) {
            if (! $policyIssuance?->completed_step || $policyIssuance?->completed_step === AwnicEnum::STEP_UPLOAD_DOCUMENTS) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = AwnicEnum::ALL_STEPS_ARE_EDITABLE;
            } elseif ($policyIssuance?->completed_step === AwnicEnum::STEP_ISSUE_POLICY) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'Upload Documents and Update Booking Details are editable';
            } elseif ($policyIssuance?->completed_step === AwnicEnum::STEP_UPLOAD_POLICY_DOCS) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'Booking Details is editable';
            }

            // Single return for this group
            return $response;
        }

        // handle no policyIssuance
        if (! $policyIssuance) {
            $response['isEditPolicyDetailsDisabled'] = false;
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = AwnicEnum::ALL_STEPS_ARE_EDITABLE;
        } elseif (
            $policyIssuance?->status === PolicyIssuanceEnum::PROCESSING_STATUS &&
            $policyIssuance?->completed_step === AwnicEnum::STEP_UPLOAD_POLICY_DOCS
        ) {
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = AwnicEnum::ALL_STEPS_ARE_EDITABLE;
        }

        // Final return, covers all remaining paths
        return $response;
    }
}
