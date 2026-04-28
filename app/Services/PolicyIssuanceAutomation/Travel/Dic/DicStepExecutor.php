<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Jobs\DicPolicyIssuanceStepJob;
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
     * @param  bool  $applyQuoteFailure  When false (async Bus steps), quote/AutomationFailedJob are handled by {@see DicPolicyIssuanceStepJob}.
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
                app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                    $quote,
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
            if ($applyQuoteFailure) {
                app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                    $quote,
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

        $documentUrl = $this->apiService->extractDocumentUrlFromResponse($result['data'] ?? null);
        if (! $documentUrl) {
            $message = 'Document URL missing in GetPolicyDoc response (configure extractDocumentUrlFromResponse)';
            if ($applyQuoteFailure) {
                app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                    $quote,
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

        try {
            $this->documentService->attachFromUrl($quote, $documentUrl, QuoteDocumentsEnum::TRAVEL_POLICY_SCHEDULE, 'Policy Schedule DIC');
        } catch (\Throwable $e) {
            LoggerService::error('DIC Travel: GetPolicyDoc download/upload failed', [
                'quote_code' => $quote->code,
                'exception' => $e->getMessage(),
            ]);
            if ($applyQuoteFailure) {
                app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                    $quote,
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

        return $this->responseHandler->buildStepResponse(
            PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
            true,
            'Policy document stored in IMCRM',
            null,
        );
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
            if ($applyQuoteFailure) {
                app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                    $quote,
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

        $documentUrl = $this->apiService->extractDocumentUrlFromResponse($result['data'] ?? null);
        if (! $documentUrl) {
            $message = 'Document URL missing in GetBrokerInvoice response (configure extractDocumentUrlFromResponse)';
            if ($applyQuoteFailure) {
                app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                    $quote,
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
            $this->documentService->attachFromUrl($quote, $documentUrl, QuoteDocumentsEnum::TRAVEL_TAX_INVOICE, 'Broker Invoice DIC');
        } catch (\Throwable $e) {
            LoggerService::error('DIC Travel: GetBrokerInvoice download/upload failed', [
                'quote_code' => $quote->code,
                'exception' => $e->getMessage(),
            ]);
            if ($applyQuoteFailure) {
                app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
                    $quote,
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

        return $this->responseHandler->buildStepResponse(
            PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
            true,
            'Broker invoice stored and quote marked policy issued',
            null,
        );
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
