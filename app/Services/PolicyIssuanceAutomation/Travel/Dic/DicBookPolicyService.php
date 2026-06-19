<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\SendPolicyTypeEnum;
use App\Http\Requests\BookPolicyRequest;
use App\Http\Requests\SendBookPolicyRequest;
use App\Models\PolicyIssuance;
use App\Models\TravelQuote;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\ManualCommissionUpdateService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Support\Facades\Validator;

final class DicBookPolicyService
{
    use GenericQueriesAllLobs;

    public function __construct(
        private PolicyIssuanceService $policyIssuanceService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function bookPolicy(TravelQuote $quote, PolicyIssuance $policyIssuance): array
    {
        $response = [
            'status' => false,
            'completed_step' => null,
            'error' => null,
            'message' => null,
        ];

        LoggerService::info('DIC Travel: BookPolicy step started', [
            'quote_code' => $quote->code,
            'policy_issuance_id' => $policyIssuance->id,
        ]);

        $updateBookingDetailsResponse = $this->updateBookingDetails($quote);
        if (! $updateBookingDetailsResponse['status']) {
            $this->policyIssuanceService->storePolicyIssuanceLog(
                $quote,
                [],
                [
                    'stage' => 'update_booking_details',
                    'error' => $updateBookingDetailsResponse['error'] ?? null,
                    'message' => $updateBookingDetailsResponse['message'] ?? null,
                ],
                'dic-book-policy/update-booking-details',
                PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY,
                PolicyIssuanceEnum::FAILED_STATUS,
                $policyIssuance,
            );
            $response['error'] = $updateBookingDetailsResponse['error'];
            $response['message'] = $updateBookingDetailsResponse['message'];
        } else {
            $quote->refresh();

            $preCheckResult = $this->validateBookPolicy($quote);
            if (! $preCheckResult['status']) {
                $this->policyIssuanceService->storePolicyIssuanceLog(
                    $quote,
                    [],
                    [
                        'stage' => 'validate_book_policy',
                        'error' => $preCheckResult['error'] ?? null,
                        'message' => $preCheckResult['message'] ?? null,
                    ],
                    'dic-book-policy/validate',
                    PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY,
                    PolicyIssuanceEnum::FAILED_STATUS,
                    $policyIssuance,
                );
                $response['error'] = $preCheckResult['error'];
                $response['message'] = $preCheckResult['message'];
            } else {
                try {
                    $request = new \stdClass;
                    $request->quote_id = $quote->id;
                    $request->modelType = quoteTypeCode::Travel;
                    $request->model_type = quoteTypeCode::Travel;
                    $request->is_send_policy = false;
                    $request->send_policy_type = SendPolicyTypeEnum::SAGE;
                    $request->transaction_payment_status = null;

                    LoggerService::info('DIC Travel: BookPolicy invoking Sage', [
                        'quote_code' => $quote->code,
                    ]);

                    $createSageProcessResponse = (new SageApiService)->postBookPolicyToSage($request, $quote);
                    $this->policyIssuanceService->storePolicyIssuanceLog(
                        $quote,
                        [],
                        $createSageProcessResponse,
                        '',
                        PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY,
                        $createSageProcessResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS,
                        $policyIssuance,
                    );

                    if (! $createSageProcessResponse['status']) {
                        LoggerService::info('DIC Travel: BookPolicy Sage submission failed', [
                            'quote_code' => $quote->code,
                            'message' => $createSageProcessResponse['message'] ?? null,
                        ]);
                        $response['error'] = $createSageProcessResponse['message'];
                    } else {
                        LoggerService::info('DIC Travel: BookPolicy Sage process created', [
                            'quote_code' => $quote->code,
                            'message' => $createSageProcessResponse['message'] ?? null,
                        ]);
                        $response['status'] = true;
                        $response['completed_step'] = PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY;
                        $response['message'] = 'Booking process in started! It will take some time to Complete. Come Back in a while to check the status!';
                    }
                } catch (Exception $e) {
                    LoggerService::error('DIC Travel: BookPolicy exception', [
                        'quote_code' => $quote->code,
                    ], exception: $e);
                    $response['error'] = 'Book policy failed: '.$e->getMessage();
                    $response['message'] = 'An error occurred during book policy: '.$e->getMessage();
                    $this->policyIssuanceService->storePolicyIssuanceLog(
                        $quote,
                        [],
                        ['error' => $e->getMessage(), 'exception_class' => $e::class],
                        'dic-book-policy/exception',
                        PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY,
                        PolicyIssuanceEnum::FAILED_STATUS,
                        $policyIssuance,
                    );
                }
            }
        }

        return $response;
    }

    /**
     * @return array{status: bool, error: ?string, message: ?string}
     */
    private function updateBookingDetails(TravelQuote $quote): array
    {
        LoggerService::info('DIC Travel: update booking details', ['quote_code' => $quote->code]);
        $response = ['status' => true, 'error' => null, 'message' => null];

        $updateCommission = app(ManualCommissionUpdateService::class)->updateCommissionForLeads([$quote->code]);
        if (! $updateCommission['status']) {
            LoggerService::info('DIC Travel: commission update skipped or failed', [
                'quote_code' => $quote->code,
                'message' => $updateCommission['message'] ?? null,
            ]);
        }

        $payment = $quote->payments()->mainLeadPayment()->first();
        $payments = $quote->payments;
        $bookPolicyPayload = $this->bookPolicyPayload($quote, quoteTypeCode::Travel, $payments, $quote->quoteDocuments);

        LoggerService::info('DIC Travel: booking details snapshot', [
            'quote_code' => $quote->code,
            'invoice_date' => $payment?->insurer_invoice_date,
            'insurer_tax_invoice_number' => $payment?->insurer_tax_number,
            'transaction_payment_status' => $bookPolicyPayload['transactionPaymentStatus'] ?? null,
            'broker_invoice_number' => $bookPolicyPayload['brokerInvoiceNo'] ?? null,
        ]);

        try {
            $updateBookingRequest = [
                'invoice_date' => $payment?->insurer_invoice_date,
                'insurer_tax_invoice_number' => $payment?->insurer_tax_number,
                'insurer_commmission_invoice_number' => $payment?->insurer_commmission_invoice_number,
                'discount' => $payment?->discount_value,
                'transaction_payment_status' => $bookPolicyPayload['transactionPaymentStatus'] ?? null,
                'broker_invoice_number' => $bookPolicyPayload['brokerInvoiceNo'] ?? null,
                'commission_vat_not_applicable' => $payment?->commission_vat_not_applicable,
                'commission_vat_applicable' => $payment?->commission_vat_applicable,
                'total_commission' => $payment?->commission,
                'invoice_description' => $bookPolicyPayload['invoiceDescription'] ?? null,
                'vat_on_commission' => $payment?->commission_vat,
                'commission_percentage' => $payment?->commmission_percentage,
                'payment_code' => $payment?->code,
                'model_type' => quoteTypeCode::Travel,
                'quote_id' => $quote->id,
                'through_automation' => true,
            ];

            $incomingRequestPayload = request()->all();

            try {
                request()->replace($updateBookingRequest);

                $bookPolicyRequest = new BookPolicyRequest;
                $validator = Validator::make($updateBookingRequest, $bookPolicyRequest->rules());
                $bookPolicyRequest->withValidator($validator);

                if ($validator->fails()) {
                    $response['status'] = false;
                    $response['error'] = $validator->errors()->first() ?? 'BookPolicyRequest validation failed';
                    $response['message'] = $validator->errors()->first();

                    return $response;
                }

                $updateBookingDetailsResponse = app(CentralService::class)->updateBookingDetails($updateBookingRequest, $bookPolicyRequest);

                if (! $updateBookingDetailsResponse['status']) {
                    $response['status'] = false;
                    $response['error'] = $updateBookingDetailsResponse['message'];
                    $response['message'] = $updateBookingDetailsResponse['message'];
                }
            } finally {
                request()->replace($incomingRequestPayload);
            }
        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = 'Booking update error: '.$e->getMessage();
            $response['message'] = 'An error occurred while updating booking details: '.$e->getMessage();

            LoggerService::error('DIC Travel: update booking details exception', [
                'quote_code' => $quote->code,
            ], exception: $e);
        }

        return $response;
    }

    /**
     * @return array{status: bool, error: ?string, message: ?string}
     */
    private function validateBookPolicy(TravelQuote $quote): array
    {
        $response = ['status' => true, 'error' => null, 'message' => null];

        try {
            $requestData = [
                'quote_id' => $quote->id,
                'model_type' => quoteTypeCode::Travel,
                'send_policy_type' => SendPolicyTypeEnum::SAGE,
                'is_send_policy' => false,
                'transaction_payment_status' => null,
                'through_automation' => true,
            ];

            $incomingRequestPayload = request()->all();

            try {
                request()->replace($requestData);

                $sendBookPolicyRequest = new SendBookPolicyRequest;
                $validator = Validator::make($requestData, $sendBookPolicyRequest->rules());
                $sendBookPolicyRequest->withValidator($validator);

                if ($validator->fails()) {
                    $response['status'] = false;
                    $response['error'] = $validator->errors()->first() ?? 'SendBookPolicyRequest validation failed';
                    $response['message'] = $validator->errors()->first();

                    return $response;
                }
            } finally {
                request()->replace($incomingRequestPayload);
            }

            $response['message'] = 'All book policy prerequisites validated successfully';
        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = 'Validation error: '.$e->getMessage();
            $response['message'] = 'An error occurred during validation: '.$e->getMessage();
        }

        return $response;
    }
}
