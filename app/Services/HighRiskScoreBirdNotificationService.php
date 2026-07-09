<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Jobs\NotifyHighRiskScoreBirdJob;
use App\Models\QuoteDocument;
use App\Services\Logger\LoggerService;

/**
 * Dispatches Bird compliance notifications when a quote's AML risk score first enters the High Risk band.
 * Bands align with {@see resources/views/pdf/risk_score_document.blade.php} (>= 35 = High Risk).
 */
class HighRiskScoreBirdNotificationService
{
    private const RISK_SCORE_DOC_STORAGE_DISK = 'azureIMPrivate';
    private const RISK_SCORE_DOC_URL_EXPIRY_MINUTES = 180;

    public function __construct(
        private readonly QuoteDocumentService $quoteDocumentService,
    ) {}

    /**
     * Queues the Bird workflow when the score newly enters the High Risk band and attaches a signed PDF URL when available.
     *
     * @param  mixed  $quote  Quote model with code, names, email.
     * @param  mixed  $uploadResult  Return value from {@see QuoteDocumentService::uploadQuoteDocument()}
     * @param  int  $riskScore  Current AML total score sent to Bird (same band as the risk score PDF).
     */
    public function queueHighRiskBirdNotification(
        mixed $quote,
        string $type,
        mixed $uploadResult,
        int $riskScore,
    ): void {
        $toRecipient = getAppStorageValueByKey(ApplicationStorageEnums::HIGH_RISK_SCORE_NOTIFICATION_TO_RECIPIENT);
        if (! $toRecipient) {
            LoggerService::warning('HighRiskScoreBirdNotificationService: To recipient not configured — skipping', context: [
                'quote_code' => $quote->code ?? null,
                'risk_score' => $riskScore,
            ]);

            return;
        }

        $ccRecipient = getAppStorageValueByKey(ApplicationStorageEnums::HIGH_RISK_SCORE_NOTIFICATION_CC_RECIPIENT);
        $riskScorePdfUrl = $this->resolveRiskScoreDocumentTemporaryUrl($uploadResult);

        NotifyHighRiskScoreBirdJob::dispatch([
            'refId' => $quote->code ?? '',
            'scoreProfile' => strtolower($type) === 'business' ? 'entity' : 'individual',
            'customerId' => $toRecipient,
            'customerEmail' => $toRecipient,
            'firstName' => $quote->first_name,
            'lastName' => $quote->last_name,
            'customerMobile' => $quote->phone,
            'riskScoreDoc' => $riskScorePdfUrl,
            'riskScore' => $riskScore,
            'ccRecipient' => $ccRecipient,
        ]);
    }

    private function resolveRiskScoreDocumentTemporaryUrl(mixed $uploadResult): ?string
    {
        if (! $uploadResult instanceof QuoteDocument || ! $uploadResult->doc_url) {
            return null;
        }

        return $this->quoteDocumentService->getDocumentUrl(
            $uploadResult->doc_url,
            self::RISK_SCORE_DOC_STORAGE_DISK,
            self::RISK_SCORE_DOC_URL_EXPIRY_MINUTES
        );
    }
}
