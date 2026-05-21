<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Models\YachtQuote;
use App\Models\YachtQuoteRequestDetail;
use App\Services\CQF\NonMotor\LOBs\YachtCQFQuoteStorageService;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
    $this->service = app(YachtCQFQuoteStorageService::class);
});

it('stores copied YachtQuote with renewal source and new lead status from PersonalQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Yacht]);

    YachtQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'source' => 'direct',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'advisor_id' => 99,
    ]);

    $oldPq->load('yachtQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Yacht,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'advisor_id' => null,
        'code' => 'YCH-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyYachtQuoteDetail($nq, $oq),
        $this->service,
        YachtCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $created = YachtQuote::where('personal_quote_id', $newPq->id)->first();

    expect($created)->not->toBeNull()
        ->and($created->source)->toBe(LeadSourceEnum::RENEWAL_UPLOAD)
        ->and($created->quote_status_id)->toBe(QuoteStatusEnum::NewLead)
        ->and($created->advisor_id)->toBeNull();
});

it('stores copied YachtQuote with renewal_batch_id from new PersonalQuote', function () {
    $oldPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Yacht,
        'renewal_batch_id' => 100,
    ]);

    YachtQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'renewal_batch_id' => 999,
    ]);

    $oldPq->load('yachtQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Yacht,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'renewal_batch_id' => 42,
        'code' => 'YCH-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyYachtQuoteDetail($nq, $oq),
        $this->service,
        YachtCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(YachtQuote::where('personal_quote_id', $newPq->id)->value('renewal_batch_id'))->toBe(42);
});

it('creates YachtQuoteRequestDetail when old YachtQuote has one', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Yacht]);

    $oldYachtQuote = YachtQuote::factory()->create(['personal_quote_id' => $oldPq->id]);
    YachtQuoteRequestDetail::factory()->create(['yacht_quote_request_id' => $oldYachtQuote->id]);

    $oldPq->load('yachtQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Yacht,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'YCH-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyYachtQuoteDetail($nq, $oq),
        $this->service,
        YachtCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $newYachtQuote = YachtQuote::where('personal_quote_id', $newPq->id)->first();

    expect($newYachtQuote)->not->toBeNull()
        ->and(YachtQuoteRequestDetail::where('yacht_quote_request_id', $newYachtQuote->id)->exists())->toBeTrue();
});

it('skips YachtQuoteRequestDetail creation when old YachtQuote has none', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Yacht]);

    YachtQuote::factory()->create(['personal_quote_id' => $oldPq->id]);

    $oldPq->load('yachtQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Yacht,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'YCH-NEW-'.Str::upper(Str::random(4)),
    ]);

    $countBefore = YachtQuoteRequestDetail::count();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyYachtQuoteDetail($nq, $oq),
        $this->service,
        YachtCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(YachtQuoteRequestDetail::count())->toBe($countBefore);
});

it('sets previous_quote_id to the old YachtQuote id on copy', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Yacht]);

    $oldYachtQuote = YachtQuote::factory()->create(['personal_quote_id' => $oldPq->id]);

    $oldPq->load('yachtQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Yacht,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'YCH-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyYachtQuoteDetail($nq, $oq),
        $this->service,
        YachtCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(YachtQuote::where('personal_quote_id', $newPq->id)->value('previous_quote_id'))
        ->toBe($oldYachtQuote->id);
});

it('leaves premium and policy_number null on copied YachtQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Yacht]);

    YachtQuote::factory()->create(['personal_quote_id' => $oldPq->id]);

    $oldPq->load('yachtQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Yacht,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'YCH-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyYachtQuoteDetail($nq, $oq),
        $this->service,
        YachtCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $created = YachtQuote::where('personal_quote_id', $newPq->id)->first();

    expect($created->premium)->toBeNull()
        ->and($created->policy_number)->toBeNull()
        ->and($created->insurance_provider_id)->toBeNull();
});

it('carries vessel detail fields from old YachtQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Yacht]);

    YachtQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'first_name' => 'Bob',
        'last_name' => 'Sailor',
        'sum_insured_value' => 250000,
        'operator_experience' => 5,
    ]);

    $oldPq->load('yachtQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Yacht,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'YCH-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyYachtQuoteDetail($nq, $oq),
        $this->service,
        YachtCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $created = YachtQuote::where('personal_quote_id', $newPq->id)->first();

    expect($created->first_name)->toBe('Bob')
        ->and($created->last_name)->toBe('Sailor')
        ->and($created->sum_insured_value)->toBe(250000)
        ->and($created->operator_experience)->toBe(5);
});

it('skips copying when old PersonalQuote has no YachtQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Yacht]);
    $newPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Yacht]);

    $countBefore = YachtQuote::count();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyYachtQuoteDetail($nq, $oq),
        $this->service,
        YachtCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(YachtQuote::count())->toBe($countBefore);
});
