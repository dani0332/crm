<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Services\RenewalsUploadService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
});

if (! function_exists('createRenewalsUploadServiceWithMocks')) {
    function createRenewalsUploadServiceWithMocks(): RenewalsUploadService
    {
        static $reflection = null;

        if ($reflection === null) {
            $reflection = new ReflectionClass(RenewalsUploadService::class);
        }

        return $reflection->newInstanceWithoutConstructor();
    }
}

test('previous_quote_id resolves to personal_quotes id when code matches', function () {
    $previousQuote = PersonalQuote::factory()->create([
        'code' => 'CAR-PREVCODE',
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    $resolvedId = PersonalQuote::where('code', 'CAR-PREVCODE')->value('id');

    expect($resolvedId)->toBe($previousQuote->id);
});

test('previous_quote_id resolves to null when no personal quote matches the code', function () {
    $resolvedId = PersonalQuote::where('code', 'CAR-DOESNOTEXIST')->value('id');

    expect($resolvedId)->toBeNull();
});

test('updatePersonalQuote persists previous_quote_id from personal_quotes id not from lob table id', function () {
    $previousQuote = PersonalQuote::factory()->create([
        'code' => 'CAR-PREV',
        'quote_type_id' => QuoteTypeId::Car,
    ]);
    $newQuote = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    // Simulate a LOB table id that differs from personal_quotes.id — the pre-fix bug used this.
    $lobTableId = $previousQuote->id + 999;

    $service = createRenewalsUploadServiceWithMocks();
    $service->updatePersonalQuote($newQuote->uuid, QuoteTypeId::Car, [
        'quote_id' => $lobTableId,
        'previous_quote_id' => $previousQuote->id,
    ]);

    expect($newQuote->refresh()->previous_quote_id)
        ->toBe($previousQuote->id)
        ->not->toBe($lobTableId);
});
