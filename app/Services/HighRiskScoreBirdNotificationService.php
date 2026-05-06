<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\NotifyHighRiskScoreBirdJob;

/**
 * Dispatches Bird compliance notifications when a quote's AML risk score first enters the High Risk band.
 * Bands align with {@see resources/views/pdf/risk_score_document.blade.php} (>= 35 = High Risk).
 */
class HighRiskScoreBirdNotificationService
{
    /**
     * @param  mixed  $quote  Lead/quote model used in calculateScore
     * @param  array{total?: int, score_list?: mixed}  $results
     * @param  string|null  $riskScorePdfUrl  HTTPS URL of the uploaded risk-score PDF (Azure temporary URL)
     */
    public function dispatchIfEligible(
        mixed $quote,
        string $type,
        array $results,
        mixed $previousRiskScore,
        ?string $riskScorePdfUrl = null,
    ): void {
        $total = (int) ($results['total'] ?? 0);
        if ($total < NotifyHighRiskScoreBirdJob::HIGH_RISK_MIN_SCORE) {
            return;
        }
        if ($previousRiskScore !== null && (int) $previousRiskScore >= NotifyHighRiskScoreBirdJob::HIGH_RISK_MIN_SCORE) {
            return;
        }

        $customerName = trim((string) (($quote->first_name ?? '').' '.($quote->last_name ?? '')));

        NotifyHighRiskScoreBirdJob::dispatch([
            'refId' => $quote->code ?? '',
            'scoreProfile' => strtolower($type) === 'business' ? 'entity' : 'individual',
            'customerEmail' => $quote->email ?? null,
            'customerName' => $customerName !== '' ? $customerName : null,
            'riskScoreDoc' => $riskScorePdfUrl,
        ]);
    }
}
