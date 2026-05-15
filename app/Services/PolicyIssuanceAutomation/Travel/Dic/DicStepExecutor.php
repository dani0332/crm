<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Models\PolicyIssuance;
use App\Models\TravelQuote;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

class DicStepExecutor
{
    public function __construct(
        private DicApiService $apiService,
        private DicResponseHandler $responseHandler,
        private DicDocumentService $documentService,
        private DicBookPolicyService $bookPolicyService,
    ) {}

    /**
     * @param  bool  $applyQuoteFailure  When false (async Bus steps), quote / Bird failed-allocation email are handled by {@see DicPolicyIssuanceStepJob}.
     * @return array<string, mixed>
     */
    public function executeIssuePolicyStep(TravelQuote $quote, PolicyIssuance $process, bool $applyQuoteFailure = true): array
    {
        LoggerService::info('DIC Travel: IssuePolicy step', [
            'process_id' => $process->id,
            'quote_code' => $quote->code,
        ]);

        $result = $this->apiService->issuePolicy($quote, $process);

        if (! $result['status']) {
            if ($applyQuoteFailure) {
                app(PolicyIssuanceService::class)->applyTravelDicAutomationResult(
                    $quote,
                    false,
                    PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
                    PolicyIssuanceEnum::PROCESS_INVOLVED_ISSUE_POLICY,
                );
            }

            return $this->withTravelDicFailureMeta(
                $result,
                PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_ISSUE_POLICY,
            );
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function executeGetPolicyDocStep(TravelQuote $quote, PolicyIssuance $process, bool $applyQuoteFailure = true): array
    {
        LoggerService::info('DIC Travel: GetPolicyDoc step', [
            'process_id' => $process->id,
            'quote_code' => $quote->code,
        ]);

        $result = $this->apiService->getPolicyDoc($quote, $process);

        if (! $result['status']) {
            return $this->buildGetPolicyDocResponseAfterFailedApi($quote, $applyQuoteFailure, $result);
        }

        return $this->completeGetPolicyDocStepAfterSuccessfulApi($quote, $applyQuoteFailure, $result);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function buildGetPolicyDocResponseAfterFailedApi(TravelQuote $quote, bool $applyQuoteFailure, array $result): array
    {
        if ($applyQuoteFailure) {
            app(PolicyIssuanceService::class)->applyTravelDicAutomationResult(
                $quote,
                false,
                PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_DOCUMENTS,
            );
        }

        return $this->withTravelDicFailureMeta(
            $result,
            PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
            PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_DOCUMENTS,
        );
    }

    /**
     * Validation failure before document download (missing schedule or tax invoice URL in API payload).
     *
     * @return array<string, mixed>
     */
    private function buildGetPolicyDocStepValidationFailureResponse(
        TravelQuote $quote,
        bool $applyQuoteFailure,
        string $message,
    ): array {
        if ($applyQuoteFailure) {
            app(PolicyIssuanceService::class)->applyTravelDicAutomationResult(
                $quote,
                false,
                PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            );
        }

        $built = $this->responseHandler->buildStepResponse(PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC, false, $message, $message);

        return $this->withTravelDicFailureMeta(
            $built,
            PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
            PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
        );
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function completeGetPolicyDocStepAfterSuccessfulApi(
        TravelQuote $quote,
        bool $applyQuoteFailure,
        array $result,
    ): array {
        $payload = $result['data'] ?? null;
        $documentUrl = $this->apiService->extractDocumentUrlFromResponse($payload);
        $taxInvoiceUrl = null;
        $validationFailure = null;

        if (! $documentUrl) {
            $validationFailure = $this->buildGetPolicyDocStepValidationFailureResponse(
                $quote,
                $applyQuoteFailure,
                'Document URL missing in GetPolicyDoc response (configure extractDocumentUrlFromResponse)',
            );
        } else {
            $taxInvoiceUrl = $this->apiService->extractTaxInvoiceUrlFromGetPolicyDocResponse($payload);
            if (! $taxInvoiceUrl) {
                $validationFailure = $this->buildGetPolicyDocStepValidationFailureResponse(
                    $quote,
                    $applyQuoteFailure,
                    'Tax Invoice URL missing in GetPolicyDoc response (expected additionalDetails.documents with documentName TAX_INVOICE)',
                );
            }
        }

        if ($validationFailure !== null) {
            return $validationFailure;
        }

        try {
            $this->documentService->attachFromUrl($quote, $documentUrl, QuoteDocumentsEnum::TRAVEL_POLICY_SCHEDULE, 'Policy Schedule DIC');
            $this->documentService->attachFromUrl($quote, $taxInvoiceUrl, QuoteDocumentsEnum::TRAVEL_TAX_INVOICE, 'Tax Invoice DIC');

            $taxInvoiceDocumentNumber = $this->apiService->extractTaxInvoiceDocumentNumberFromGetPolicyDocResponse($payload);
            if (is_string($taxInvoiceDocumentNumber) && $taxInvoiceDocumentNumber !== '') {
                $payment = $quote->payments()->mainLeadPayment()->first();
                if ($payment !== null) {
                    $payment->update(['insurer_tax_number' => $taxInvoiceDocumentNumber]);
                    LoggerService::info('DIC Travel: GetPolicyDoc stored insurer_tax_number on main payment', [
                        'quote_code' => $quote->code,
                        'payment_code' => $payment->code,
                    ]);
                } else {
                    LoggerService::warning('DIC Travel: GetPolicyDoc could not update insurer_tax_number — main lead payment not found', [
                        'quote_code' => $quote->code,
                    ]);
                }
            }

            return $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
                true,
                'Policy schedule and tax invoice stored in IMCRM',
                null,
            );
        } catch (\Throwable $e) {
            LoggerService::error('DIC Travel: GetPolicyDoc download/upload failed', [
                'quote_code' => $quote->code,
                'exception' => $e->getMessage(),
            ]);
            if ($applyQuoteFailure) {
                app(PolicyIssuanceService::class)->applyTravelDicAutomationResult(
                    $quote,
                    false,
                    PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
                    PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
                );
            }

            $built = $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
                false,
                $e->getMessage(),
                $e->getMessage(),
            );

            return $this->withTravelDicFailureMeta(
                $built,
                PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function executeGetBrokerInvoiceStep(TravelQuote $quote, PolicyIssuance $process, bool $applyQuoteFailure = true): array
    {
        LoggerService::info('DIC Travel: GetBrokerInvoice step', [
            'process_id' => $process->id,
            'quote_code' => $quote->code,
        ]);

        $result = $this->apiService->getBrokerInvoice($quote, $process);

        if (! $result['status']) {
            return $this->buildGetBrokerInvoiceResponseAfterFailedApi($quote, $applyQuoteFailure, $result);
        }

        return $this->completeGetBrokerInvoiceStepAfterSuccessfulApi($quote, $applyQuoteFailure, $result);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function buildGetBrokerInvoiceResponseAfterFailedApi(TravelQuote $quote, bool $applyQuoteFailure, array $result): array
    {
        if ($applyQuoteFailure) {
            app(PolicyIssuanceService::class)->applyTravelDicAutomationResult(
                $quote,
                false,
                PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
            );
        }

        return $this->withTravelDicFailureMeta(
            $result,
            PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
            PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
        );
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function completeGetBrokerInvoiceStepAfterSuccessfulApi(
        TravelQuote $quote,
        bool $applyQuoteFailure,
        array $result,
    ): array {
        $documentUrl = $this->apiService->extractDocumentUrlFromResponse($result['data'] ?? null);
        if (! $documentUrl) {
            $message = 'Document URL missing in GetBrokerInvoice response (configure extractDocumentUrlFromResponse)';
            if ($applyQuoteFailure) {
                app(PolicyIssuanceService::class)->applyTravelDicAutomationResult(
                    $quote,
                    false,
                    PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
                    PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
                );
            }

            $built = $this->responseHandler->buildStepResponse(PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE, false, $message, $message);

            return $this->withTravelDicFailureMeta(
                $built,
                PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
            );
        }

        try {
            $this->documentService->attachFromUrl($quote, $documentUrl, QuoteDocumentsEnum::TRAVEL_TAX_INVOICE_RAISE_BY_BUYER, 'Broker Invoice DIC');

            $invoiceNumber = $this->apiService->extractBrokerInvoiceNumberFromResponse($result['data'] ?? null);
            if (is_string($invoiceNumber) && $invoiceNumber !== '') {
                $payment = $quote->payments()->mainLeadPayment()->first();
                if ($payment !== null) {
                    $payment->update([
                        'insurer_commmission_invoice_number' => $invoiceNumber,
                    ]);
                    LoggerService::info('DIC Travel: GetBrokerInvoice stored insurer_commmission_invoice_number on main payment', [
                        'quote_code' => $quote->code,
                        'payment_code' => $payment->code,
                    ]);
                } else {
                    LoggerService::warning('DIC Travel: GetBrokerInvoice could not update insurer_commmission_invoice_number — main lead payment not found', [
                        'quote_code' => $quote->code,
                    ]);
                }
            }

            return $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
                true,
                'Broker invoice stored and quote marked policy issued',
                null,
            );
        } catch (\Throwable $e) {
            LoggerService::error('DIC Travel: GetBrokerInvoice download/upload failed', [
                'quote_code' => $quote->code,
                'exception' => $e->getMessage(),
            ]);
            if ($applyQuoteFailure) {
                app(PolicyIssuanceService::class)->applyTravelDicAutomationResult(
                    $quote,
                    false,
                    PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
                    PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
                );
            }

            $built = $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
                false,
                $e->getMessage(),
                $e->getMessage(),
            );

            return $this->withTravelDicFailureMeta(
                $built,
                PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
            );
        }
    }

    /**
     * @param  bool  $applyQuoteFailure  When false (async Bus steps), quote / Bird failed-allocation email are handled by {@see DicPolicyIssuanceStepJob}.
     * @return array<string, mixed>
     */
    public function executeBookPolicyStep(TravelQuote $quote, PolicyIssuance $process, bool $applyQuoteFailure = true): array
    {
        LoggerService::info('DIC Travel: BookPolicy step', [
            'process_id' => $process->id,
            'quote_code' => $quote->code,
        ]);

        $result = $this->bookPolicyService->bookPolicy($quote, $process);

        if (! $result['status']) {
            if ($applyQuoteFailure) {
                app(PolicyIssuanceService::class)->applyTravelDicAutomationResult(
                    $quote,
                    false,
                    PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
                    PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
                );
            }

            return $this->withTravelDicFailureMeta(
                $result,
                PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
            );
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function withTravelDicFailureMeta(array $response, int $insurerApiStatusId, string $processInvolved): array
    {
        return array_merge($response, [
            'travel_dic_insurer_api_status_id' => $insurerApiStatusId,
            'travel_dic_process_involved' => $processInvolved,
        ]);
    }
}
