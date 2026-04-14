<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Models\Customer;
use App\Models\PersonalQuote;
use App\Services\AlfredCoinsWebhookService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\SchemaUtils;

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
