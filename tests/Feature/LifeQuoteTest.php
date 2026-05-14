<?php

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Services\OCR\OCRService;
use Tests\Helpers\LifeQuoteMockHelper;
use Tests\Helpers\LifeQuoteTestDataBuilder;
use Tests\Helpers\TestDataSeeder;

beforeEach(function () {
    $this->lookups = TestDataSeeder::seedLifeQuoteLookups();
    $this->user = TestDataSeeder::createAdminUser();

    // Mock OCRService to avoid dependency resolution issues in HandleInertiaRequests middleware
    $ocrServiceMock = Mockery::mock(OCRService::class);
    $ocrServiceMock->shouldReceive('getEligibleProviders')->andReturn([]);
    $this->app->instance(OCRService::class, $ocrServiceMock);

    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
});

test('can create a life quote using actual controller method', function () {
    $testUuid = 'test-quote-uuid-'.uniqid();
    $quoteData = LifeQuoteTestDataBuilder::buildQuoteData([], $this->lookups);

    LifeQuoteMockHelper::mockCapiRequestService($testUuid);

    $response = $this->post(route('life-quotes-store'), $quoteData);

    $response->assertRedirect(route('life-quotes-show', $testUuid));
    $response->assertSessionHas('message', 'Quote is created successfully.');

    $personalQuote = PersonalQuote::where('uuid', $testUuid)->firstOrFail();
    expect($personalQuote->first_name)->toBe($quoteData['first_name'])
        ->and($personalQuote->last_name)->toBe($quoteData['last_name'])
        ->and($personalQuote->email)->toBe($quoteData['email'])
        ->and($personalQuote->quote_type_id)->toBe(QuoteTypeId::Life)
        ->and($personalQuote->created_by_id)->toBe($this->user->id);

    $lifeQuote = $personalQuote->lifeQuote;
    expect($lifeQuote)->not->toBeNull()
        ->and($lifeQuote->first_name)->toBe($quoteData['first_name'])
        ->and($lifeQuote->last_name)->toBe($quoteData['last_name'])
        ->and($lifeQuote->email)->toBe($quoteData['email'])
        ->and($lifeQuote->sum_insured_value)->toBe($quoteData['sum_insured_value'])
        ->and($lifeQuote->is_smoker)->toBe($quoteData['is_smoker'])
        ->and($lifeQuote->gender)->toBe($quoteData['gender'])
        ->and($lifeQuote->height)->toBe($quoteData['height'])
        ->and($lifeQuote->weight)->toBe($quoteData['weight'])
        ->and($lifeQuote->bmi)->toBe($quoteData['bmi'])
        ->and($lifeQuote->age)->toBe($quoteData['age']);
});

test('validates required fields when creating life quote', function () {
    $response = $this->post(route('life-quotes-store'), []);

    $response->assertSessionHasErrors([
        'first_name',
        'last_name',
        'email',
        'mobile_no',
        'dob',
        'sum_insured_value',
        'nationality_id',
        'sum_insured_currency_id',
        'marital_status_id',
        'purpose_of_insurance_id',
        'number_of_years_id',
        'is_smoker',
        'gender',
        'height',
        'weight',
        'bmi',
        'age',
    ]);

    $response->assertStatus(302);
});
