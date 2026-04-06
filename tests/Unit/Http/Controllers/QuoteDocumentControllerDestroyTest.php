<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\QuoteDocument;
use App\Services\QuoteDocumentService;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

afterEach(function () {
    Mockery::close();
});

test('destroy does not delete document when policy is locked', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);
    $this->actingAs($user);

    $quote = CarQuote::factory()->create([
        'advisor_id' => $user->id,
        'quote_status_id' => 71,
    ]);

    $document = QuoteDocument::factory()->carQuote()->forQuote($quote->id)->create();

    $quoteDocumentService = Mockery::mock(QuoteDocumentService::class);
    $quoteDocumentService
        ->shouldReceive('isEnableUploadDocument')
        ->once()
        ->with($quote->quote_status_id)
        ->andReturn(false);
    $this->app->instance(QuoteDocumentService::class, $quoteDocumentService);

    $this->from('/quotes/business/8U8SCYTZ')
        ->post('/documents/delete', [
            'doc_id' => $document->id,
            'doc_uuid' => $document->doc_uuid,
        ])
        ->assertRedirect('/quotes/business/8U8SCYTZ')
        ->assertSessionHas('error', 'Document cannot be deleted as the policy is locked.');

    expect($document->fresh())->not->toBeNull();
});

test('destroy does not delete document when car advisor is not assigned to the quote', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, ['email' => 'advisor-a@example.com']);
    $otherAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, ['email' => 'advisor-b@example.com']);
    $this->actingAs($otherAdvisor);

    $quote = CarQuote::factory()->create([
        'advisor_id' => $advisor->id,
        'quote_status_id' => 1,
    ]);

    $document = QuoteDocument::factory()->carQuote()->forQuote($quote->id)->create();

    $this->from('/quotes/car/test-uuid')
        ->post('/documents/delete', [
            'doc_id' => $document->id,
            'doc_uuid' => $document->doc_uuid,
        ])
        ->assertRedirect('/quotes/car/test-uuid')
        ->assertSessionHas('error', 'You are not authorized to perform this action on this quote.');

    expect($document->fresh())->not->toBeNull();
});
