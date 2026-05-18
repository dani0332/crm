<?php

namespace App\Traits;

use App\Mail\EpFailureNotification;
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
     * Send EP failure notification email for ECB and other embedded products.
     *
     * General purchase/document failures dedupe on {@see EmbeddedTransaction::$failure_email_sent_at}.
     * Manual Sage booking retry failures dedupe on {@see EmbeddedTransaction::$sage_booking_failure_email_sent_at}
     * so a prior purchase-flow failure email does not block the Sage-specific notification.
     */
    protected function sendEpFailureEmail(int $quoteId, int $quoteTypeId, int $etId, string $logPrefix, bool $isSageBooking = false): void
    {
        try {
            $transaction = EmbeddedTransaction::find($etId);

            if ($isSageBooking) {
                if ($transaction && ! empty($transaction->sage_booking_failure_email_sent_at)) {
                    LoggerService::info("{$logPrefix} Sage booking EP failure email already sent, skipping", extra: [
                        'etId' => $etId,
                        'sage_booking_failure_email_sent_at' => $transaction->sage_booking_failure_email_sent_at,
                    ]);

                    return;
                }
            } elseif ($transaction && ! empty($transaction->failure_email_sent_at)) {
                LoggerService::info("{$logPrefix} EP failure email already sent, skipping", extra: [
                    'etId' => $etId,
                    'failure_email_sent_at' => $transaction->failure_email_sent_at,
                ]);

                return;
            }

            Mail::send(new EpFailureNotification($quoteId, $quoteTypeId, $etId, $isSageBooking));

            $timestampColumn = $isSageBooking ? 'sage_booking_failure_email_sent_at' : 'failure_email_sent_at';

            DB::table('embedded_transactions')
                ->where('id', $etId)
                ->update([$timestampColumn => now()]);

            LoggerService::info("{$logPrefix} Embedded Product failure email sent successfully", extra: [
                'etId' => $etId,
                'isSageBooking' => $isSageBooking,
            ]);
        } catch (Throwable $e) {
            LoggerService::error("{$logPrefix} Failed to send Embedded Product failure email: ".$e->getMessage(), extra: [
                'etId' => $etId,
                'isSageBooking' => $isSageBooking,
            ]);
        }
    }
}
