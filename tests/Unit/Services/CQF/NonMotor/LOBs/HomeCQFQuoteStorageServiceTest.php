<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\HomeQuote;
use App\Models\HomeQuoteRequestDetail;
use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\LOBs\HomeCQFQuoteStorageService;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
    $this->service = app(HomeCQFQuoteStorageService::class);
});

it('stores copied HomeQuote with renewal source and new lead status from PersonalQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Home]);

    HomeQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'source' => 'direct',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'advisor_id' => 99,
    ]);

    $oldPq->load('homeQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Home,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'advisor_id' => null,
        'code' => 'HOM-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyHomeQuoteDetail($nq, $oq),
        $this->service,
        HomeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $created = HomeQuote::where('personal_quote_id', $newPq->id)->first();

    expect($created)->not->toBeNull()
        ->and($created->source)->toBe(LeadSourceEnum::RENEWAL_UPLOAD)
        ->and($created->quote_status_id)->toBe(QuoteStatusEnum::NewLead)
        ->and($created->advisor_id)->toBeNull();
});

it('stores copied HomeQuote with renewal_batch_id from new PersonalQuote', function () {
    $oldPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Home,
        'renewal_batch_id' => 100,
    ]);

    HomeQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'renewal_batch_id' => 999,
    ]);

    $oldPq->load('homeQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Home,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'renewal_batch_id' => 42,
        'code' => 'HOM-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyHomeQuoteDetail($nq, $oq),
        $this->service,
        HomeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(HomeQuote::where('personal_quote_id', $newPq->id)->value('renewal_batch_id'))->toBe(42);
});

it('creates HomeQuoteRequestDetail when old HomeQuote has one', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Home]);

    $oldHomeQuote = HomeQuote::factory()->create(['personal_quote_id' => $oldPq->id]);
    HomeQuoteRequestDetail::factory()->create(['home_quote_request_id' => $oldHomeQuote->id]);

    $oldPq->load('homeQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Home,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'HOM-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyHomeQuoteDetail($nq, $oq),
        $this->service,
        HomeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $newHomeQuote = HomeQuote::where('personal_quote_id', $newPq->id)->first();

    expect($newHomeQuote)->not->toBeNull()
        ->and(HomeQuoteRequestDetail::where('home_quote_request_id', $newHomeQuote->id)->exists())->toBeTrue();
});

it('skips HomeQuoteRequestDetail creation when old HomeQuote has none', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Home]);

    HomeQuote::factory()->create(['personal_quote_id' => $oldPq->id]);

    $oldPq->load('homeQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Home,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'HOM-NEW-'.Str::upper(Str::random(4)),
    ]);

    $countBefore = HomeQuoteRequestDetail::count();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyHomeQuoteDetail($nq, $oq),
        $this->service,
        HomeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(HomeQuoteRequestDetail::count())->toBe($countBefore);
});

it('sets previous_quote_id to the old HomeQuote id on copy', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Home]);

    $oldHomeQuote = HomeQuote::factory()->create(['personal_quote_id' => $oldPq->id]);

    $oldPq->load('homeQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Home,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'HOM-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyHomeQuoteDetail($nq, $oq),
        $this->service,
        HomeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(HomeQuote::where('personal_quote_id', $newPq->id)->value('previous_quote_id'))
        ->toBe($oldHomeQuote->id);
});

it('leaves premium and policy_number null on copied HomeQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Home]);

    HomeQuote::factory()->create(['personal_quote_id' => $oldPq->id]);

    $oldPq->load('homeQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Home,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'HOM-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyHomeQuoteDetail($nq, $oq),
        $this->service,
        HomeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $created = HomeQuote::where('personal_quote_id', $newPq->id)->first();

    expect($created->premium)->toBeNull()
        ->and($created->policy_number)->toBeNull()
        ->and($created->insurance_provider_id)->toBeNull();
});

it('carries property and personal detail fields from old HomeQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Home]);

    HomeQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'building_value' => 500000,
        'has_building' => true,
        'has_contents' => false,
    ]);

    $oldPq->load('homeQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Home,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'HOM-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyHomeQuoteDetail($nq, $oq),
        $this->service,
        HomeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $created = HomeQuote::where('personal_quote_id', $newPq->id)->first();

    expect($created->first_name)->toBe('Alice')
        ->and($created->last_name)->toBe('Smith')
        ->and($created->building_value)->toBe(500000)
        ->and((bool) $created->has_building)->toBeTrue()
        ->and((bool) $created->has_contents)->toBeFalse();
});

it('skips copying when old PersonalQuote has no HomeQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Home]);
    $newPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Home]);

    $countBefore = HomeQuote::count();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyHomeQuoteDetail($nq, $oq),
        $this->service,
        HomeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(HomeQuote::count())->toBe($countBefore);
});
