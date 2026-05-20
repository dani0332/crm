<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AlfredCoinsWebhookService
{
    use GenericQueriesAllLobs;

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

        $this->attemptInsuranceMarketWebhook($quoteUID, $quoteTypeId);
    }

    private function attemptInsuranceMarketWebhook(string $quoteUID, int $quoteTypeId): void
    {
        $config = config('services.alfred_coins.insurancemarket_webhook', []);
        $url = $config['url'] ?? null;
        $privateKey = $config['private_key'] ?? null;
        $timeout = $config['timeout'] ?? 15;

        if (empty($url) || empty($privateKey)) {
            LoggerService::info('AlfredCoinsWebhookService - Skipping webhook (missing url or private_key)', [], [
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

        if (empty($payload)) {
            LoggerService::error('AlfredCoinsWebhookService - Payload not built for quote due to missing amount', [], null, [
                'quoteUID' => $quoteUID,
                'quoteTypeId' => $quoteTypeId,
            ]);

            return;
        }

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
                    'x-webhook-token' => $this->generateJwt($payload, $privateKey),
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

    private function resolveQuote(string $uuid, int $quoteTypeId): ?Model
    {
        $quoteTypeEnum = QuoteTypes::getName($quoteTypeId);
        if (! $quoteTypeEnum instanceof QuoteTypes) {
            return null;
        }

        $quote = $this->getQuoteObject($quoteTypeEnum->value, $uuid);

        if ($quote === false || ! $quote instanceof Model) {
            $quote = PersonalQuote::query()
                ->where('uuid', $uuid)
                ->where('quote_type_id', $quoteTypeId)
                ->first();
        }

        if (
            $quote instanceof PersonalQuote &&
            (int) $quote->getAttribute('quote_type_id') !== $quoteTypeId) {
            return null;
        }

        return $quote;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Model $quote): array
    {
        $leadSource = $quote->getAttribute('source');
        $eventName = $leadSource === LeadSourceEnum::RENEWAL_UPLOAD
            ? self::EVENT_RENEWED
            : self::EVENT_PURCHASED;

        $mainPayment = $quote->payments()?->mainLeadPayment()?->first();
        $amount = $mainPayment?->price_vat_applicable;

        if ($amount === null) {
            LoggerService::error('AlfredCoinsWebhookService - Amount not found for quote', [], null, [
                'quoteUID' => $quote->getAttribute('uuid'),
                'quoteTypeId' => $quote->getAttribute('quote_type_id'),
            ]);

            return [];
        }

        return [
            'email' => $quote->getAttribute('email'),
            'eventName' => $eventName,
            'occurredAt' => now()->toISOString(),
            'source' => self::PAYLOAD_SOURCE,
            'reason' => self::REASON,
            'uniqueId' => $quote->getAttribute('code'),
            'amount' => (float) $amount,
            'currency' => self::CURRENCY,
        ];
    }

    private function generateJwt(array $data, string $privateKey): string
    {
        $now = time();

        if (! str_contains($privateKey, '-----BEGIN')) {
            $privateKey = "-----BEGIN PRIVATE KEY-----\n"
                .wordwrap(str_replace(["\r", "\n", ' '], '', $privateKey), 64, "\n", true)
                ."\n-----END PRIVATE KEY-----";
        }

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => 'imcrm-v1',
        ], JSON_THROW_ON_ERROR));

        $payload = $this->base64UrlEncode(json_encode([
            'iss' => 'imcrm',
            'iat' => $now,
            'exp' => $now + 300,
            'jti' => (string) Str::uuid(),
            'data' => $data,
        ], JSON_THROW_ON_ERROR));

        $signingInput = $header.'.'.$payload;

        if (openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256) === false) {
            throw new RuntimeException('Failed to sign JWT: '.openssl_error_string());
        }

        return $signingInput.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
