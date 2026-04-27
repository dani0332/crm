<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteStatusEnum;
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
    ) {}

    /**
     * @param  PolicyIssuance  $process
     */
    public function executeIssuePolicyStep(TravelQuote $quote, $process): array
    {
        LoggerService::info('DIC Travel: IssuePolicy step', [
            'process_id' => $process->id,
            'quote_code' => $quote->code,
        ]);

        $result = $this->apiService->issuePolicy($quote, $process);

        if (! $result['status']) {
            app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                $quote,
                PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_ISSUE_POLICY,
            );

            return $result;
        }

        return $result;
    }

    /**
     * @param  PolicyIssuance  $process
     */
    public function executeGetPolicyDocStep(TravelQuote $quote, $process): array
    {
        LoggerService::info('DIC Travel: GetPolicyDoc step', [
            'process_id' => $process->id,
            'quote_code' => $quote->code,
        ]);

        $result = $this->apiService->getPolicyDoc($quote, $process);

        if (! $result['status']) {
            app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                $quote,
                PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_DOCUMENTS,
            );

            return $result;
        }

        $documentUrl = $this->apiService->extractDocumentUrlFromResponse($result['data'] ?? null);
        if (! $documentUrl) {
            $message = 'Document URL missing in GetPolicyDoc response (configure extractDocumentUrlFromResponse)';
            app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                $quote,
                PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            );

            return $this->responseHandler->buildStepResponse(PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC, false, $message, $message);
        }

        try {
            $this->documentService->attachFromUrl($quote, $documentUrl, QuoteDocumentsEnum::TRAVEL_POLICY_SCHEDULE, 'Policy Schedule DIC');
        } catch (\Throwable $e) {
            LoggerService::error('DIC Travel: GetPolicyDoc download/upload failed', [
                'quote_code' => $quote->code,
                'exception' => $e->getMessage(),
            ]);
            app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                $quote,
                PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            );

            return $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
                false,
                $e->getMessage(),
                $e->getMessage(),
            );
        }

        return $this->responseHandler->buildStepResponse(
            PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
            true,
            'Policy document stored in IMCRM',
            null,
        );
    }

    /**
     * @param  PolicyIssuance  $process
     */
    public function executeGetBrokerInvoiceStep(TravelQuote $quote, $process): array
    {
        LoggerService::info('DIC Travel: GetBrokerInvoice step', [
            'process_id' => $process->id,
            'quote_code' => $quote->code,
        ]);

        $result = $this->apiService->getBrokerInvoice($quote, $process);

        if (! $result['status']) {
            app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                $quote,
                PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
            );

            return $result;
        }

        $documentUrl = $this->apiService->extractDocumentUrlFromResponse($result['data'] ?? null);
        if (! $documentUrl) {
            $message = 'Document URL missing in GetBrokerInvoice response (configure extractDocumentUrlFromResponse)';
            app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                $quote,
                PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
            );

            return $this->responseHandler->buildStepResponse(PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE, false, $message, $message);
        }

        try {
            $this->documentService->attachFromUrl($quote, $documentUrl, QuoteDocumentsEnum::TRAVEL_TAX_INVOICE, 'Broker Invoice DIC');
        } catch (\Throwable $e) {
            LoggerService::error('DIC Travel: GetBrokerInvoice download/upload failed', [
                'quote_code' => $quote->code,
                'exception' => $e->getMessage(),
            ]);
            app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                $quote,
                PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
            );

            return $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
                false,
                $e->getMessage(),
                $e->getMessage(),
            );
        }

        /* $quote->update([
            'quote_status_id' => QuoteStatusEnum::PolicyIssued,
            'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued,
            'quote_status_date' => now(),
        ]); */

        return $this->responseHandler->buildStepResponse(
            PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
            true,
            'Broker invoice stored and quote marked policy issued',
            null,
        );
    }
}
