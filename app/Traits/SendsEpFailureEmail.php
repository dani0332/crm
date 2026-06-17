<?php

namespace App\Traits;

use App\Mail\EpFailureNotification;
use App\Mail\EpReversalFailureNotification;
use App\Mail\SukoonMedexEPFailureNotification;
use App\Models\EmbeddedTransaction;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

trait SendsEpFailureEmail
{
    /**
     * Send EP failure notification email for Sukoon Medex
     *
     * @param  mixed  $quoteObject
     * @param  int|null  $quoteTypeId
     * @param  int|null  $transactionId
     */
    protected function sendSukoonMedexFailureEmail($quoteObject, $quoteTypeId, $transactionId, string $logPrefix): void
    {
        if (! $transactionId) {
            LoggerService::warning("{$logPrefix} Cannot send failure email - transaction ID not found");

            return;
        }

        // Check if email was already sent
        $transaction = EmbeddedTransaction::find($transactionId);
        if ($transaction && ! empty($transaction->failure_email_sent_at)) {
            LoggerService::info("{$logPrefix} EP failure email already sent, skipping", extra: [
                'transactionId' => $transactionId,
                'failure_email_sent_at' => $transaction->failure_email_sent_at,
            ]);

            return;
        }

        try {
            Mail::send(new SukoonMedexEPFailureNotification($quoteObject, $quoteTypeId));

            // Update failure_email_sent_at after successful send
            DB::table('embedded_transactions')
                ->where('id', $transactionId)
                ->update(['failure_email_sent_at' => now()]);

            LoggerService::info("{$logPrefix} Send EP failure notification email successfully", extra: [
                'transactionId' => $transactionId,
            ]);
        } catch (Throwable $emailException) {
            LoggerService::error("{$logPrefix} Send EP failure notification email Failed", extra: [
                'exception' => $emailException->getMessage(),
                'transactionId' => $transactionId,
            ]);
        }
    }

    /**
     * Send EP failure notification email for ECB and other embedded products
     */
    protected function sendEpFailureEmail(int $quoteId, int $quoteTypeId, int $etId, string $logPrefix): void
    {
        // Check if email was already sent
        $transaction = EmbeddedTransaction::find($etId);
        if ($transaction && ! empty($transaction->failure_email_sent_at)) {
            LoggerService::info("{$logPrefix} EP failure email already sent, skipping", extra: [
                'etId' => $etId,
                'failure_email_sent_at' => $transaction->failure_email_sent_at,
            ]);

            return;
        }

        try {
            Mail::send(new EpFailureNotification($quoteId, $quoteTypeId, $etId));

            // Update failure_email_sent_at after successful send
            DB::table('embedded_transactions')
                ->where('id', $etId)
                ->update(['failure_email_sent_at' => now()]);

            LoggerService::info("{$logPrefix} Embedded Product failure email sent successfully", extra: [
                'etId' => $etId,
            ]);
        } catch (Throwable $e) {
            LoggerService::error("{$logPrefix} Failed to send Embedded Product failure email: ".$e->getMessage(), extra: [
                'etId' => $etId,
            ]);
        }
    }

    /**
     * Notify finance/engineering when Sage EP booking reversal exhausts retries or the job fails.
     */
    protected function sendEpReversalFailureEmail(int $quoteId, int $quoteTypeId, int $etId, string $logPrefix): void
    {
        try {
            Mail::send(new EpReversalFailureNotification($quoteId, $quoteTypeId, $etId));
            LoggerService::info("{$logPrefix} EP Sage reversal failure email sent", extra: [
                'etId' => $etId,
            ]);
        } catch (Throwable $e) {
            LoggerService::error("{$logPrefix} Failed to send EP Sage reversal failure email: ".$e->getMessage(), extra: [
                'etId' => $etId,
            ]);
        }
    }
}
