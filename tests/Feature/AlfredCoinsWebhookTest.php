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

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    SchemaUtils::addColumnIfMissing('personal_quotes', 'premium', fn (Blueprint $table) => $table->decimal('premium', 12, 2)->nullable());
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
});

test('sends insurance_purchased webhook with expected payload for car quote', function () {
    $webhookUrl = 'https://api-stage-alfredcoins.test/webhook/upload/insurancemarket';
    Config::set('services.alfred_coins.insurancemarket_webhook.url', $webhookUrl);
    Config::set('services.alfred_coins.insurancemarket_webhook.api_key', 'test-secret-key');
    Config::set('services.alfred_coins.insurancemarket_webhook.api_key_header', 'X-API-Key');

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
        $data = $request->data();

        return $request->hasHeader('X-API-Key', 'test-secret-key')
            && ($data['eventName'] ?? null) === 'insurance_purchased'
            && ($data['source'] ?? null) === 'insurancemarket'
            && ($data['email'] ?? null) === 'customer@example.com'
            && ($data['uniqueId'] ?? null) === $quote->code
            && (float) ($data['amount'] ?? 0) === 4000.5
            && ($data['currency'] ?? null) === 'AED'
            && ($data['reason'] ?? null) === 'Policy purchased from InsuranceMarket.ae';
    });
});

test('logs webhook dispatch metadata without exposing payload in log context', function () {
    $captured = null;

    Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$captured): void {
        if (str_contains($event->message, 'Sending InsuranceMarket webhook')) {
            $captured = $event;
        }
    });

    $webhookUrl = 'https://api-stage-alfredcoins.test/webhook/upload/insurancemarket';
    Config::set('services.alfred_coins.insurancemarket_webhook.url', $webhookUrl);
    Config::set('services.alfred_coins.insurancemarket_webhook.api_key', 'test-secret-key');

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
    $webhookUrl = 'https://api-stage-alfredcoins.test/webhook/upload/insurancemarket';
    Config::set('services.alfred_coins.insurancemarket_webhook.url', $webhookUrl);
    Config::set('services.alfred_coins.insurancemarket_webhook.api_key', 'test-secret-key');

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
        $data = $request->data();

        return ($data['eventName'] ?? null) === 'insurance_renewed'
            && ($data['source'] ?? null) === 'insurancemarket';
    });
});

test('skips webhook for business and group medical quote types', function () {
    $webhookUrl = 'https://api-stage-alfredcoins.test/webhook/upload/insurancemarket';
    Config::set('services.alfred_coins.insurancemarket_webhook.url', $webhookUrl);
    Config::set('services.alfred_coins.insurancemarket_webhook.api_key', 'test-secret-key');

    Http::fake();

    app(AlfredCoinsWebhookService::class)->sendInsuranceMarketWebhook(
        'non-existent-uuid',
        QuoteTypeId::Business
    );

    app(AlfredCoinsWebhookService::class)->sendInsuranceMarketWebhook(
        'non-existent-uuid-2',
        QuoteTypeId::GroupMedical
    );

    Http::assertNothingSent();
});
