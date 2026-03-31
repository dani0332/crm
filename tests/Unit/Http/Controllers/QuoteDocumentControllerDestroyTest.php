<?php

declare(strict_types=1);

use App\Models\CarQuote;
use App\Models\QuoteDocument;
use App\Services\QuoteDocumentService;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('destroy does not delete document when policy is locked', function () {
    $user = TestDataSeeder::createUser();
    $this->actingAs($user);

    $quote = CarQuote::factory()->create([
        'advisor_id' => $user->id,
        'quote_status_id' => 71,
    ]);

    $document = QuoteDocument::factory()->forQuote($quote->id)->create();

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
