<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
});

test('returns utm details for a valid quote uuid and type', function () {
    $quote = PersonalQuote::factory()->createForSqlite([
        'uuid' => 'TEST-UTM-01',
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    PersonalQuoteDetail::factory()->withUtm('google', 'cpc', 'summer-sale')->create([
        'personal_quote_id' => $quote->id,
    ]);

    $response = $this->post(route('get-utm-details'), [
        'uuid' => 'TEST-UTM-01',
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    $response->assertOk()
        ->assertJsonPath('record.uuid', 'TEST-UTM-01')
        ->assertJsonPath('record.quote_detail.utm_source', 'google')
        ->assertJsonPath('record.quote_detail.utm_medium', 'cpc')
        ->assertJsonPath('record.quote_detail.utm_campaign', 'summer-sale');
});

test('returns 404 when quote is not found', function () {
    $response = $this->post(route('get-utm-details'), [
        'uuid' => 'NON-EXISTENT',
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    $response->assertNotFound()
        ->assertJsonPath('error', 'Quote not found');
});

test('returns 422 when uuid is missing', function () {
    $response = $this->post(route('get-utm-details'), [
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['error']);
});

test('returns 422 when quote_type_id is missing', function () {
    $response = $this->post(route('get-utm-details'), [
        'uuid' => 'TEST-UTM-01',
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['error']);
});

test('returns 403 for user without view-utm-section permission', function () {
    $plainUser = TestDataSeeder::createUser(['email' => 'noutm@example.com']);
    $this->actingAs($plainUser);

    $response = $this->post(route('get-utm-details'), [
        'uuid' => 'ANY-UUID',
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    $response->assertForbidden();
});
