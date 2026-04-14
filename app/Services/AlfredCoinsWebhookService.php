<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\Http;

class AlfredCoinsWebhookService
{
    private const REASON = 'Policy purchased from InsuranceMarket.ae';
    private const PAYLOAD_SOURCE = 'insurancemarket';
    private const CURRENCY = 'AED';
    private const EVENT_PURCHASED = 'insurance_purchased';
    private const EVENT_RENEWED = 'insurance_renewed';

    /**
     * POST InsuranceMarket Alfred Coins webhook for a policy-booked quote.
     */
    public function sendInsuranceMarketWebhook(string $quoteUID, int $quoteTypeId): void
    {
        if ($this->shouldSkipForQuoteType($quoteTypeId)) {
            return;
        }

        $config = config('services.alfred_coins.insurancemarket_webhook', []);
        $url = $config['url'] ?? null;
        $apiKey = $config['api_key'] ?? null;
        $headerName = $config['api_key_header'] ?? 'X-API-Key';
        $timeout = $config['timeout'] ?? 15;

        if (empty($url) || empty($apiKey)) {
            LoggerService::info('AlfredCoinsWebhookService - Skipping webhook (missing url or api_key)', [], [
                'quoteUID' => $quoteUID,
                'quoteTypeId' => $quoteTypeId,
            ]);

            return;
        }

        $quote = $this->resolveQuote($quoteUID, $quoteTypeId);
        if (! $quote) {
            LoggerService::warning('AlfredCoinsWebhookService - Quote not found for webhook', [], null, [
                'quoteUID' => $quoteUID,
                'quoteTypeId' => $quoteTypeId,
            ]);

            return;
        }

        $payload = $this->buildPayload($quote);

        LoggerService::info('AlfredCoinsWebhookService - Sending InsuranceMarket webhook', [
            'payload' => $payload,
        ], [
            'webhookUrl' => $url,
            'quoteUID' => $quoteUID,
            'quoteTypeId' => $quoteTypeId,
        ]);

        try {
            $response = Http::timeout((int) $timeout)
                ->withHeaders([
                    $headerName => $apiKey,
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                LoggerService::info('AlfredCoinsWebhookService - Webhook accepted', [], [
                    'quoteUID' => $quoteUID,
                    'status' => $response->status(),
                ]);
            } else {
                LoggerService::error('AlfredCoinsWebhookService - Webhook returned non-success status', [], null, [
                    'quoteUID' => $quoteUID,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Exception $e) {
            LoggerService::error('AlfredCoinsWebhookService - Webhook request failed', [], $e, [
                'quoteUID' => $quoteUID,
                'quoteTypeId' => $quoteTypeId,
            ]);
        }
    }

    private function shouldSkipForQuoteType(int $quoteTypeId): bool
    {
        return in_array($quoteTypeId, [
            QuoteTypeId::Business,
            QuoteTypeId::GroupMedical,
        ], true);
    }

    private function resolveQuote(string $uuid, int $quoteTypeId): ?PersonalQuote
    {
        return PersonalQuote::query()
            ->where('uuid', $uuid)
            ->where('quote_type_id', $quoteTypeId)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(PersonalQuote $quote): array
    {
        $leadSource = $quote->getAttribute('source');
        $eventName = $leadSource === LeadSourceEnum::RENEWAL_UPLOAD
            ? self::EVENT_RENEWED
            : self::EVENT_PURCHASED;

        $amount = $quote->premium ?? null;
        if ($amount === null && isset($quote->price_with_vat)) {
            $amount = $quote->price_with_vat;
        }

        return [
            'email' => $quote->getAttribute('email'),
            'eventName' => $eventName,
            'source' => self::PAYLOAD_SOURCE,
            'reason' => self::REASON,
            'uniqueId' => $quote->getAttribute('code'),
            'amount' => $amount !== null ? (float) $amount : null,
            'currency' => self::CURRENCY,
        ];
    }
}
