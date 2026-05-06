<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\NotifyHighRiskScoreBirdJob;
use App\Models\QuoteDocument;

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
     * @param  mixed  $quote  Lead/quote model used in calculateScore
     * @param  array{total?: int, score_list?: mixed}  $results
     * @param  mixed  $latestPersistedRiskScore  Latest `risk_score` stored on the quote row before this run (null if unset).
     * @param  mixed  $uploadResult  Return value from {@see QuoteDocumentService::uploadQuoteDocument()}
     */
    public function queueHighRiskBirdNotification(
        mixed $quote,
        string $type,
        mixed $uploadResult,
    ): void {
        $riskScorePdfUrl = $this->resolveRiskScoreDocumentTemporaryUrl($uploadResult);

        $customerName = trim((string) (($quote->first_name ?? '').' '.($quote->last_name ?? '')));

        NotifyHighRiskScoreBirdJob::dispatch([
            'refId' => $quote->code ?? '',
            'scoreProfile' => strtolower($type) === 'business' ? 'entity' : 'individual',
            'customerEmail' => $quote->email ?? null,
            'customerName' => $customerName !== '' ? $customerName : null,
            'riskScoreDoc' => $riskScorePdfUrl,
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
