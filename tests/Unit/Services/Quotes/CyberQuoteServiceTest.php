<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Models\Lookup;
use App\Models\PersonalQuote;
use App\Services\Quotes\CyberQuoteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createCyberSchema();

    $this->service = app(CyberQuoteService::class);
});

describe('CyberQuoteService transaction type', function () {
    it('populates transaction_type_text from the eager-loaded transactionType relation', function () {
        $transactionType = Lookup::factory()->create(['text' => 'New Business']);

        $quoteId = DB::table('personal_quotes')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'CYB-TEST-'.uniqid(),
            'quote_type_id' => QuoteTypeId::Cyber,
            'transaction_type_id' => $transactionType->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quote = PersonalQuote::query()->findOrFail($quoteId);

        $result = $this->service->getOne($quote->uuid, true);

        expect($result->transaction_type_text)->toBe('New Business');
    });

    it('leaves transaction_type_text null when transaction_type_id is not set', function () {
        $quoteId = DB::table('personal_quotes')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'CYB-TEST-'.uniqid(),
            'quote_type_id' => QuoteTypeId::Cyber,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quote = PersonalQuote::query()->findOrFail($quoteId);

        $result = $this->service->getOne($quote->uuid, true);

        expect($result->transaction_type_text)->toBeNull();
    });
});
