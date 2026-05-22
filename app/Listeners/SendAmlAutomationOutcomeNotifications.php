<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\QuoteTypes;
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

    public function handle(AmlAutomationScreeningSucceeded $event): void
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
        // IMCRM outcome mail uses only the payment_link returned here (not a broader submit-docs workflow).
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
            quote: $quote,
            paymentLink: $paymentLink
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

}
