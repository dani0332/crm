<?php

declare(strict_types=1);

use App\Enums\CollectionTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Services\AlfredCoinsWebhookService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\SchemaUtils;

/**
 * Persist a payment with a fixed VAT-applicable amount. {@see PaymentObserver} overwrites
 * `price_vat_applicable` from `total_price` on create; disabling events keeps test amounts stable.
 */
function createAlfredCoinsTestPayment(PersonalQuote $quote, float $priceVatApplicable): void
{
    Payment::withoutEvents(function () use ($quote, $priceVatApplicable): void {
        $quote->payments()->create([
            'code' => $quote->code,
            'price_vat_applicable' => $priceVatApplicable,
            'payment_status_id' => PaymentStatusEnum::NEW,
            'payment_methods_code' => PaymentMethodsEnum::InsurerPayment,
            'collection_type' => CollectionTypeEnum::INSURER,
            'frequency' => PaymentFrequency::UPFRONT,
            'total_payments' => 1,
            'discount_value' => 0,
            'captured_amount' => 0,
        ]);
    });
}

/**
 * Decode the payload section of a JWT without signature verification.
 *
 * @return array<string, mixed>
 */
function decodeJwtPayload(string $token): array
{
    $parts = explode('.', $token);
    $json = base64_decode(strtr($parts[1] ?? '', '-_', '+/'));

    return (array) json_decode((string) $json, true);
}

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    SchemaUtils::addColumnIfMissing('personal_quotes', 'premium', fn (Blueprint $table) => $table->decimal('premium', 12, 2)->nullable());
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);

    $keyResource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    expect($keyResource)->not->toBeFalse('openssl_pkey_new() failed — check OpenSSL config');
    openssl_pkey_export($keyResource, $this->testPrivateKey);
});

test('sends insurance_purchased webhook with x-webhook-token JWT containing expected data payload', function () {
    $webhookUrl = 'https://api-stage-alfredcoins.test/webhook/upload/imcrm';
    Config::set('services.alfred_coins.insurancemarket_webhook.url', $webhookUrl);
    Config::set('services.alfred_coins.insurancemarket_webhook.private_key', $this->testPrivateKey);

    Http::fake([
        $webhookUrl => Http::response(['ok' => true], 200),
    ]);

    $customer = Customer::factory()->create();
    $quote = PersonalQuote::query()->create([
        'uuid' => 'CAR-WEBHOOK-UUID',
        'code' => 'CAR-WEBHOOK-CODE',
        'quote_type_id' => QuoteTypeId::Car,
        'customer_id' => $customer->id,
        'source' => LeadSourceEnum::WEB,
        'email' => 'customer@example.com',
        'premium' => 4000.50,
    ]);

    createAlfredCoinsTestPayment($quote, 4000.50);

    app(AlfredCoinsWebhookService::class)->sendInsuranceMarketWebhook(
        $quote->uuid,
        QuoteTypeId::Car
    );

    Http::assertSent(function ($request) use ($webhookUrl, $quote) {
        if ($request->url() !== $webhookUrl) {
            return false;
        }

        $token = $request->header('x-webhook-token')[0] ?? null;
        $jwtData = $token ? (decodeJwtPayload($token)['data'] ?? []) : [];
        $body = $request->data();

        return $token !== null
            && ($jwtData['eventName'] ?? null) === 'insurance_purchased'
            && ($jwtData['source'] ?? null) === 'insurancemarket'
            && ($jwtData['email'] ?? null) === 'customer@example.com'
            && ($jwtData['uniqueId'] ?? null) === $quote->code
            && (float) ($jwtData['amount'] ?? 0) === 4000.5
            && ($jwtData['currency'] ?? null) === 'AED'
            && ($jwtData['reason'] ?? null) === 'Policy purchased from InsuranceMarket.ae'
            && ($body['email'] ?? null) === 'customer@example.com'
            && (float) ($body['amount'] ?? 0) === 4000.5;
    });
});

test('JWT token contains correct RS256 claims structure', function () {
    $webhookUrl = 'https://api-stage-alfredcoins.test/webhook/upload/imcrm';
    Config::set('services.alfred_coins.insurancemarket_webhook.url', $webhookUrl);
    Config::set('services.alfred_coins.insurancemarket_webhook.private_key', $this->testPrivateKey);

    Http::fake([
        $webhookUrl => Http::response(['ok' => true], 200),
    ]);

    $customer = Customer::factory()->create();
    $quote = PersonalQuote::query()->create([
        'uuid' => 'CAR-JWT-CLAIMS-UUID',
        'code' => 'CAR-JWT-CLAIMS-CODE',
        'quote_type_id' => QuoteTypeId::Car,
        'customer_id' => $customer->id,
        'source' => LeadSourceEnum::WEB,
        'email' => 'jwt@example.com',
        'premium' => 1000.00,
    ]);

    createAlfredCoinsTestPayment($quote, 1000.00);

    $before = time();
    app(AlfredCoinsWebhookService::class)->sendInsuranceMarketWebhook($quote->uuid, QuoteTypeId::Car);
    $after = time();

    Http::assertSent(function ($request) use ($before, $after) {
        $token = $request->header('x-webhook-token')[0] ?? null;
        if ($token === null) {
            return false;
        }

        $parts = explode('.', $token);
        $jwtHeader = (array) json_decode((string) base64_decode(strtr($parts[0] ?? '', '-_', '+/')), true);
        $claims = decodeJwtPayload($token);

        return count($parts) === 3
            && ($jwtHeader['alg'] ?? null) === 'RS256'
            && ($jwtHeader['typ'] ?? null) === 'JWT'
            && ($jwtHeader['kid'] ?? null) === 'imcrm-v1'
            && ($claims['iss'] ?? null) === 'imcrm'
            && ! empty($claims['jti'])
            && ($claims['iat'] ?? 0) >= $before
            && ($claims['iat'] ?? 0) <= $after
            && ($claims['exp'] ?? 0) === ($claims['iat'] ?? 0) + 300
            && is_array($claims['data'] ?? null);
    });
});

test('logs webhook dispatch metadata without exposing payload in log context', function () {
    $captured = null;

    Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$captured): void {
        if (str_contains($event->message, 'Sending InsuranceMarket webhook')) {
            $captured = $event;
        }
    });

    $webhookUrl = 'https://api-stage-alfredcoins.test/webhook/upload/imcrm';
    Config::set('services.alfred_coins.insurancemarket_webhook.url', $webhookUrl);
    Config::set('services.alfred_coins.insurancemarket_webhook.private_key', $this->testPrivateKey);

    Http::fake([
        $webhookUrl => Http::response(['ok' => true], 200),
    ]);

    $customer = Customer::factory()->create();
    $quote = PersonalQuote::query()->create([
        'uuid' => 'CAR-LOG-PAYLOAD-UUID',
        'code' => 'CAR-LOG-PAYLOAD-CODE',
        'quote_type_id' => QuoteTypeId::Car,
        'customer_id' => $customer->id,
        'source' => LeadSourceEnum::WEB,
        'email' => 'logged@example.com',
        'premium' => 2500.00,
    ]);

    createAlfredCoinsTestPayment($quote, 2500.00);

    app(AlfredCoinsWebhookService::class)->sendInsuranceMarketWebhook(
        $quote->uuid,
        QuoteTypeId::Car
    );

    expect($captured)->not->toBeNull()
        ->and($captured->level)->toBe('info')
        ->and($captured->context['webhookUrl'] ?? null)->toBe($webhookUrl)
        ->and($captured->context)->not->toHaveKey('payload');

    Http::assertSent(function ($request) use ($webhookUrl, $quote): bool {
        if ($request->url() !== $webhookUrl) {
            return false;
        }

        $data = $request->data();

        return ($data['eventName'] ?? null) === 'insurance_purchased'
            && ($data['email'] ?? null) === 'logged@example.com'
            && ($data['uniqueId'] ?? null) === $quote->code
            && (float) ($data['amount'] ?? 0) === 2500.0;
    });
});

test('sends insurance_renewed event when lead source is renewal upload and keeps payload source insurancemarket', function () {
    $webhookUrl = 'https://api-stage-alfredcoins.test/webhook/upload/imcrm';
    Config::set('services.alfred_coins.insurancemarket_webhook.url', $webhookUrl);
    Config::set('services.alfred_coins.insurancemarket_webhook.private_key', $this->testPrivateKey);

    Http::fake([
        $webhookUrl => Http::response(['ok' => true], 200),
    ]);

    $customer = Customer::factory()->create();
    $quote = PersonalQuote::query()->create([
        'uuid' => 'CAR-RENEW-UUID',
        'code' => 'CAR-RENEW-CODE',
        'quote_type_id' => QuoteTypeId::Car,
        'customer_id' => $customer->id,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'email' => 'renew@example.com',
        'premium' => 100,
    ]);

    createAlfredCoinsTestPayment($quote, 100.0);

    app(AlfredCoinsWebhookService::class)->sendInsuranceMarketWebhook(
        $quote->uuid,
        QuoteTypeId::Car
    );

    Http::assertSent(function ($request) {
        $token = $request->header('x-webhook-token')[0] ?? null;
        $jwtData = $token ? (decodeJwtPayload($token)['data'] ?? []) : [];
        $body = $request->data();

        return ($jwtData['eventName'] ?? null) === 'insurance_renewed'
            && ($jwtData['source'] ?? null) === 'insurancemarket'
            && ($body['eventName'] ?? null) === 'insurance_renewed';
    });
});

test('skips webhook for business and group medical quote types', function () {
    Http::fake();

    app(AlfredCoinsWebhookService::class)->sendInsuranceMarketWebhook('non-existent-uuid', QuoteTypeId::Business);
    app(AlfredCoinsWebhookService::class)->sendInsuranceMarketWebhook('non-existent-uuid-2', QuoteTypeId::GroupMedical);

    Http::assertNothingSent();
});

test('skips webhook when private_key is not configured', function () {
    Config::set('services.alfred_coins.insurancemarket_webhook.url', 'https://api-stage-alfredcoins.test/webhook/upload/imcrm');
    Config::set('services.alfred_coins.insurancemarket_webhook.private_key', null);

    Http::fake();

    app(AlfredCoinsWebhookService::class)->sendInsuranceMarketWebhook('any-uuid', QuoteTypeId::Car);

    Http::assertNothingSent();
});
