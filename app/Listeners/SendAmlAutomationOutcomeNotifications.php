<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AmlAutomationScreeningFailed;
use App\Events\AmlAutomationScreeningSucceeded;
use App\Mail\Aml\AmlAutomationOutcomeMail;
use App\Models\PersonalQuote;
use App\Services\KenService;
use App\Services\Logger\LoggerService;
use App\Support\AmlQuoteAutomation\AmlAutomatableLobRegistry;

class SendAmlAutomationOutcomeNotifications
{
    public function handleSucceeded(AmlAutomationScreeningSucceeded $event): void
    {
        if (! AmlAutomatableLobRegistry::allows($event->quoteType)) {
            return;
        }

        $quote = PersonalQuote::query()->with('advisor')->find($event->quoteRequestId);
        if (! $quote) {
            LoggerService::info('AML automation success email skipped: quote not found', [
                'quote_request_id' => $event->quoteRequestId,
            ]);

            return;
        }

        // TODO:: UNCOMMENT THIS AFTER TESTING
        // $ken = app(KenService::class);
        // $kenResult = $ken->submitSukoonDocuments($event->quoteUuid, 'imcrm');

        $kenResult = [];
        $paymentLink = $kenResult['payment_link'] ?? null;
        $paymentLink = 'https://www.google.com'; // TODO:: remove this after testing

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

        $quote = PersonalQuote::query()->with('advisor')->find($event->quoteRequestId);
        if (! $quote) {
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
