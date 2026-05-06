<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\QuoteTypes;
use App\Events\AmlAutomationScreeningFailed;
use App\Events\AmlAutomationScreeningSucceeded;
use App\Mail\Aml\AmlAutomationOutcomeMail;
use App\Services\KenService;
use App\Services\Logger\LoggerService;
use App\Support\AmlQuoteAutomation\AmlAutomatableLobRegistry;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Database\Eloquent\Model;

class SendAmlAutomationOutcomeNotifications
{
    use GenericQueriesAllLobs;

    public function handleSucceeded(AmlAutomationScreeningSucceeded $event): void
    {
        if (! AmlAutomatableLobRegistry::allows($event->quoteType)) {
            return;
        }

        $quote = $this->resolveQuoteForAutomationOutcome($event->quoteType, $event->quoteRequestId);
        if ($quote === null) {
            LoggerService::info('AML automation success email skipped: quote not found', [
                'quote_request_id' => $event->quoteRequestId,
                'quote_type' => $event->quoteType->value,
            ]);

            return;
        }

        $ken = app(KenService::class);
        $kenResult = $ken->submitSukoonDocuments($event->quoteUuid, 'imcrm');
        $paymentLink = $kenResult['payment_link'] ?? null;

        if (empty($paymentLink)) {
            LoggerService::error('AML automation success email skipped: payment link not found, most probably customer has not signed and submitted the documents yet...', [
                'quote_request_id' => $event->quoteRequestId,
                'ken_result' => $kenResult,
            ]);

            return;
        }

        $mail = new AmlAutomationOutcomeMail(
            isSuccess: true,
            quote: $quote,
            paymentLink: $paymentLink,
            failureReason: null,
            kenResponseSummary: $this->summarizeKenResponse($kenResult),
        );

        $mail->sendViaBird();
    }

    public function handleFailed(AmlAutomationScreeningFailed $event): void
    {
        if (! AmlAutomatableLobRegistry::allows($event->quoteType)) {
            return;
        }

        $quote = $this->resolveQuoteForAutomationOutcome($event->quoteType, $event->quoteRequestId);
        if ($quote === null) {
            LoggerService::info('AML automation failure email skipped: quote not found', [
                'quote_request_id' => $event->quoteRequestId,
                'quote_type' => $event->quoteType->value,
            ]);

            return;
        }

        $mail = new AmlAutomationOutcomeMail(
            isSuccess: false,
            quote: $quote,
            paymentLink: null,
            failureReason: $event->failureReason,
            kenResponseSummary: null,
        );

        $mail->sendViaBird();
    }

    private function resolveQuoteForAutomationOutcome(QuoteTypes $quoteType, int $quoteRequestId): ?Model
    {
        $resolved = $this->getQuoteObject($quoteType->value, $quoteRequestId);
        if ($resolved === false) {
            return null;
        }

        $resolved->loadMissing('advisor');

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $kenResult
     */
    private function summarizeKenResponse(array $kenResult): ?string
    {
        if (($kenResult['success'] ?? false) !== true) {
            return isset($kenResult['error']) ? (string) $kenResult['error'] : 'KEN request failed';
        }

        $body = $kenResult['body'] ?? null;
        if (! is_array($body)) {
            return null;
        }

        $encoded = json_encode($body);

        if ($encoded === false) {
            return null;
        }

        return mb_substr($encoded, 0, 2000);
    }
}
