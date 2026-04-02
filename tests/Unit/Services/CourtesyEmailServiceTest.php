<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypeId;
use App\Models\ApplicationStorage;
use App\Models\QuoteFlowDetails;
use App\Services\BirdService;
use App\Services\CourtesyEmailService;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    Event::fake();
});

afterEach(function () {
    Mockery::close();
});

test('isCourtesyEmailQuoteType returns true for an allowed line of business and false otherwise', function () {
    expect(CourtesyEmailService::isCourtesyEmailQuoteType(QuoteTypeId::Car))->toBeTrue()
        ->and(CourtesyEmailService::isCourtesyEmailQuoteType(QuoteTypeId::Cyber))->toBeFalse();
});

test('getGoogleReviewFlowLogContext returns not applicable when quote type is not eligible for courtesy email', function () {
    $service = app(CourtesyEmailService::class);

    $result = $service->getGoogleReviewFlowLogContext(
        'any-uuid',
        QuoteTypeId::Cyber,
        'customer@example.com',
    );

    expect($result['review_flow_status'])->toBe('Not applicable')
        ->and($result['suppression_expires_at'])->toBe('—');
});

test('getGoogleReviewFlowLogContext returns eligible when pre-fetched courtesy flows are non-empty', function () {
    $service = app(CourtesyEmailService::class);

    $result = $service->getGoogleReviewFlowLogContext(
        'quote-uuid',
        QuoteTypeId::Car,
        'customer@example.com',
        collect([(object) ['id' => 1]]),
    );

    expect($result['review_flow_status'])->toBe('Eligible')
        ->and($result['suppression_expires_at'])->toBe('—');
});

test('processCourtesyEmailWorkflow rejects a quote type that is not allowed for courtesy email', function () {
    $service = app(CourtesyEmailService::class);

    $result = $service->processCourtesyEmailWorkflow('any-uuid', QuoteTypeId::Cyber);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Quote type not allowed for courtesy email');
});

test('processCourtesyEmailWorkflow fails when the quote is missing or has no email', function () {
    $service = app(CourtesyEmailService::class);

    $result = $service->processCourtesyEmailWorkflow('non-existent-car-quote-uuid', QuoteTypeId::Car);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Quote not found or missing email');
});

test('processCourtesyEmailWorkflow suppresses when the same customer and line of business had a courtesy flow within seven days', function () {
    $this->mock(BirdService::class, function ($mock) {
        $mock->shouldNotReceive('triggerWebHookRequest');
    });

    ApplicationStorage::updateOrInsert(
        ['key_name' => ApplicationStorageEnums::BIRD_COURTESY_EMAIL_WORKFLOW_URL],
        ['value' => 'https://example.test/courtesy', 'created_at' => now(), 'updated_at' => now()]
    );

    $advisor = TestDataSeeder::createUser([
        'email' => 'advisor-courtesy@example.com',
        'name' => 'Courtesy Advisor',
    ]);

    $sharedEmail = 'shared-courtesy@example.com';

    $quoteOlder = TestDataSeeder::createCarQuote([
        'email' => $sharedEmail,
        'advisor_id' => $advisor->id,
    ]);

    $quoteCurrent = TestDataSeeder::createCarQuote([
        'email' => $sharedEmail,
        'advisor_id' => $advisor->id,
    ]);

    QuoteFlowDetails::query()->create([
        'quote_uuid' => $quoteOlder->uuid,
        'quote_type_id' => QuoteTypeId::Car,
        'flow_type' => QuoteFlowType::COURTESY_EMAIL->value,
        'flow_id' => 'prior-courtesy-run',
        'started_at' => now()->subDay(),
    ]);

    $service = app(CourtesyEmailService::class);

    $result = $service->processCourtesyEmailWorkflow($quoteCurrent->uuid, QuoteTypeId::Car);

    expect($result['success'])->toBeFalse()
        ->and($result['suppressed'] ?? false)->toBeTrue()
        ->and($result['message'])->toContain('suppressed');
});
