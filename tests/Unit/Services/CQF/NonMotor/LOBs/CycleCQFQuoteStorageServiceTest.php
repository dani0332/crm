<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CycleQuote;
use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\LOBs\CycleCQFQuoteStorageService;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
    $this->service = (new ReflectionClass(CycleCQFQuoteStorageService::class))->newInstanceWithoutConstructor();
});

it('creates CycleQuote linked to new PersonalQuote on copy', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Cycle]);

    CycleQuote::factory()->create(['personal_quote_id' => $oldPq->id]);

    $oldPq->load('cycleQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Cycle,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'CYC-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyCycleQuoteDetail($nq, $oq),
        $this->service,
        CycleCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(CycleQuote::where('personal_quote_id', $newPq->id)->exists())->toBeTrue();
});

it('copies cycle_make and cycle_model from old CycleQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Cycle]);

    CycleQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'cycle_make' => 'Yamaha',
        'cycle_model' => 'YZF-R1',
    ]);

    $oldPq->load('cycleQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Cycle,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'CYC-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyCycleQuoteDetail($nq, $oq),
        $this->service,
        CycleCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $created = CycleQuote::where('personal_quote_id', $newPq->id)->first();

    expect($created)->not->toBeNull()
        ->and($created->cycle_make)->toBe('Yamaha')
        ->and($created->cycle_model)->toBe('YZF-R1');
});

it('takes quote_status_id from the new PersonalQuote, not the old CycleQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Cycle]);

    CycleQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    $oldPq->load('cycleQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Cycle,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'CYC-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyCycleQuoteDetail($nq, $oq),
        $this->service,
        CycleCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(CycleQuote::where('personal_quote_id', $newPq->id)->value('quote_status_id'))
        ->toBe(QuoteStatusEnum::NewLead);
});

it('leaves premium and policy_number null on copied CycleQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Cycle]);

    CycleQuote::factory()->create(['personal_quote_id' => $oldPq->id]);

    $oldPq->load('cycleQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Cycle,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'CYC-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyCycleQuoteDetail($nq, $oq),
        $this->service,
        CycleCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $created = CycleQuote::where('personal_quote_id', $newPq->id)->first();

    expect($created->quote_batch_id)->toBeNull()
        ->and($created->risk_score)->toBeNull();
});

it('skips copying when old PersonalQuote has no CycleQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Cycle]);
    $newPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Cycle]);

    $countBefore = CycleQuote::count();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyCycleQuoteDetail($nq, $oq),
        $this->service,
        CycleCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(CycleQuote::count())->toBe($countBefore);
});
