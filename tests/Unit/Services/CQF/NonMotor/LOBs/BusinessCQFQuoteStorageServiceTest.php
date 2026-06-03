<?php

declare(strict_types=1);

use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\BusinessQuote;
use App\Models\CustomerInsured;
use App\Models\CustomerMembers;
use App\Models\Insured;
use App\Models\InsuredKyc;
use App\Models\PersonalQuote;
use App\Models\QuoteRequestEntityMapping;
use App\Services\CQF\NonMotor\LOBs\BusinessCQFQuoteStorageService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
    $this->service = app(BusinessCQFQuoteStorageService::class);
});

it('stores renewal quote with correct source, status, and no advisor on BusinessQuote', function () {
    $newUuid = Str::uuid()->toString();
    $newCode = 'BUS-'.strtoupper(substr($newUuid, 0, 8));

    $oldBusinessQuote = BusinessQuote::factory()->groupMedical()->create([
        'source' => 'IMCRM',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'advisor_id' => 99,
        'assignment_type' => AssignmentTypeEnum::SYSTEM_ASSIGNED,
        'renewal_batch_id' => 888,
    ]);

    $oldQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $oldQuote->shouldReceive('getAttribute')->with('businessQuote')->andReturn($oldBusinessQuote);
    $oldQuote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($oldBusinessQuote);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $newQuote->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);
    $newQuote->shouldReceive('getAttribute')->with('quote_status_id')->andReturn(QuoteStatusEnum::NewLead);
    $newQuote->shouldReceive('getAttribute')->with('advisor_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('assignment_type')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('renewal_batch_id')->andReturn(50);
    $newQuote->shouldReceive('getAttribute')->with('uuid')->andReturn($newUuid);
    $newQuote->shouldReceive('getAttribute')->with('code')->andReturn($newCode);
    $newQuote->shouldReceive('businessQuote')->andReturn(
        Mockery::mock(BelongsTo::class)->shouldIgnoreMissing()
    );
    $newQuote->shouldReceive('save')->andReturnNull();

    // Expose the protected method via Closure binding
    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBusinessQuoteDetail($nq, $oq),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    $copyDetail($newQuote, $oldQuote);

    $createdBusinessQuote = BusinessQuote::where('uuid', $newUuid)->first();

    expect($createdBusinessQuote)->not->toBeNull()
        ->and($createdBusinessQuote->source)->toBe(LeadSourceEnum::RENEWAL_UPLOAD)
        ->and($createdBusinessQuote->quote_status_id)->toBe(QuoteStatusEnum::NewLead)
        ->and($createdBusinessQuote->advisor_id)->toBeNull()
        ->and($createdBusinessQuote->assignment_type)->toBeNull()
        ->and($createdBusinessQuote->renewal_batch_id)->toBe(50);
});

it('does not copy old source when old BusinessQuote had a different source', function () {
    $newUuid = Str::uuid()->toString();
    $newCode = 'BUS-'.strtoupper(substr($newUuid, 0, 8));

    $oldBusinessQuote = BusinessQuote::factory()->create([
        'source' => 'direct',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'advisor_id' => 7,
    ]);

    $oldQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $oldQuote->shouldReceive('getAttribute')->with('businessQuote')->andReturn($oldBusinessQuote);
    $oldQuote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($oldBusinessQuote);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $newQuote->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);
    $newQuote->shouldReceive('getAttribute')->with('quote_status_id')->andReturn(QuoteStatusEnum::NewLead);
    $newQuote->shouldReceive('getAttribute')->with('advisor_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('assignment_type')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('renewal_batch_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('uuid')->andReturn($newUuid);
    $newQuote->shouldReceive('getAttribute')->with('code')->andReturn($newCode);
    $newQuote->shouldReceive('businessQuote')->andReturn(
        Mockery::mock(BelongsTo::class)->shouldIgnoreMissing()
    );
    $newQuote->shouldReceive('save')->andReturnNull();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBusinessQuoteDetail($nq, $oq),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    $copyDetail($newQuote, $oldQuote);

    $createdBusinessQuote = BusinessQuote::where('uuid', $newUuid)->first();

    expect($createdBusinessQuote->source)->not->toBe('direct')
        ->and($createdBusinessQuote->source)->toBe(LeadSourceEnum::RENEWAL_UPLOAD);
});

it('sets previous_quote_id to the old BusinessQuote id on copy', function () {
    $oldBusinessQuote = BusinessQuote::factory()->create([
        'source' => 'direct',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    $newUuid = Str::uuid()->toString();
    $newCode = 'BUS-'.strtoupper(substr($newUuid, 0, 8));

    $oldQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $oldQuote->shouldReceive('getAttribute')->with('businessQuote')->andReturn($oldBusinessQuote);
    $oldQuote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($oldBusinessQuote);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $newQuote->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);
    $newQuote->shouldReceive('getAttribute')->with('quote_status_id')->andReturn(QuoteStatusEnum::NewLead);
    $newQuote->shouldReceive('getAttribute')->with('advisor_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('assignment_type')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('renewal_batch_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('uuid')->andReturn($newUuid);
    $newQuote->shouldReceive('getAttribute')->with('code')->andReturn($newCode);
    $newQuote->shouldReceive('businessQuote')->andReturn(
        Mockery::mock(BelongsTo::class)->shouldIgnoreMissing()
    );
    $newQuote->shouldReceive('save')->andReturnNull();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBusinessQuoteDetail($nq, $oq),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    $copyDetail($newQuote, $oldQuote);

    expect(BusinessQuote::where('uuid', $newUuid)->value('previous_quote_id'))
        ->toBe($oldBusinessQuote->id);
});

it('leaves premium and policy_number null on copied BusinessQuote', function () {
    $oldBusinessQuote = BusinessQuote::factory()->create([
        'source' => 'direct',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    $newUuid = Str::uuid()->toString();
    $newCode = 'BUS-'.strtoupper(substr($newUuid, 0, 8));

    $oldQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $oldQuote->shouldReceive('getAttribute')->with('businessQuote')->andReturn($oldBusinessQuote);
    $oldQuote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($oldBusinessQuote);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $newQuote->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);
    $newQuote->shouldReceive('getAttribute')->with('quote_status_id')->andReturn(QuoteStatusEnum::NewLead);
    $newQuote->shouldReceive('getAttribute')->with('advisor_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('assignment_type')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('renewal_batch_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('uuid')->andReturn($newUuid);
    $newQuote->shouldReceive('getAttribute')->with('code')->andReturn($newCode);
    $newQuote->shouldReceive('businessQuote')->andReturn(
        Mockery::mock(BelongsTo::class)->shouldIgnoreMissing()
    );
    $newQuote->shouldReceive('save')->andReturnNull();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBusinessQuoteDetail($nq, $oq),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    $copyDetail($newQuote, $oldQuote);

    $created = BusinessQuote::where('uuid', $newUuid)->first();

    expect($created->premium)->toBeNull()
        ->and($created->policy_number)->toBeNull()
        ->and($created->insurance_provider_id)->toBeNull();
});

it('carries company and contact fields from old BusinessQuote', function () {
    $oldBusinessQuote = BusinessQuote::factory()->create([
        'source' => 'direct',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'company_name' => 'Acme Corp',
        'number_of_employees' => 50,
    ]);

    $newUuid = Str::uuid()->toString();
    $newCode = 'BUS-'.strtoupper(substr($newUuid, 0, 8));

    $oldQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $oldQuote->shouldReceive('getAttribute')->with('businessQuote')->andReturn($oldBusinessQuote);
    $oldQuote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($oldBusinessQuote);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $newQuote->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);
    $newQuote->shouldReceive('getAttribute')->with('quote_status_id')->andReturn(QuoteStatusEnum::NewLead);
    $newQuote->shouldReceive('getAttribute')->with('advisor_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('assignment_type')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('renewal_batch_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('uuid')->andReturn($newUuid);
    $newQuote->shouldReceive('getAttribute')->with('code')->andReturn($newCode);
    $newQuote->shouldReceive('businessQuote')->andReturn(
        Mockery::mock(BelongsTo::class)->shouldIgnoreMissing()
    );
    $newQuote->shouldReceive('save')->andReturnNull();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBusinessQuoteDetail($nq, $oq),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    $copyDetail($newQuote, $oldQuote);

    $created = BusinessQuote::where('uuid', $newUuid)->first();

    expect($created->company_name)->toBe('Acme Corp')
        ->and($created->number_of_employees)->toBe(50);
});

it('skips copying when old quote has no BusinessQuote detail', function () {
    $oldQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $oldQuote->shouldReceive('getAttribute')->with('businessQuote')->andReturn(null);
    $oldQuote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn(null);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();

    $countBefore = BusinessQuote::count();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBusinessQuoteDetail($nq, $oq),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    $copyDetail($newQuote, $oldQuote);

    expect(BusinessQuote::count())->toBe($countBefore);
});

it('copies QuoteRequestEntityMapping to the new BusinessQuote on Corpline renewal', function () {
    $oldBusinessQuote = BusinessQuote::factory()->create([
        'source' => 'IMCRM',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    QuoteRequestEntityMapping::factory()->create([
        'quote_type_id' => QuoteTypeId::Business,
        'quote_request_id' => $oldBusinessQuote->id,
        'entity_id' => 10,
        'entity_type_code' => 'LLC',
    ]);

    $newUuid = Str::uuid()->toString();
    $newCode = 'BUS-'.strtoupper(substr($newUuid, 0, 8));

    $oldQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $oldQuote->shouldReceive('getAttribute')->with('businessQuote')->andReturn($oldBusinessQuote);
    $oldQuote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($oldBusinessQuote);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $newQuote->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);
    $newQuote->shouldReceive('getAttribute')->with('quote_status_id')->andReturn(QuoteStatusEnum::NewLead);
    $newQuote->shouldReceive('getAttribute')->with('advisor_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('assignment_type')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('renewal_batch_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('uuid')->andReturn($newUuid);
    $newQuote->shouldReceive('getAttribute')->with('code')->andReturn($newCode);
    $newQuote->shouldReceive('businessQuote')->andReturn(
        Mockery::mock(BelongsTo::class)->shouldIgnoreMissing()
    );
    $newQuote->shouldReceive('save')->andReturnNull();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBusinessQuoteDetail($nq, $oq),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    $copyDetail($newQuote, $oldQuote);

    $newBusinessQuote = BusinessQuote::where('uuid', $newUuid)->first();

    expect(QuoteRequestEntityMapping::where('quote_request_id', $newBusinessQuote->id)->exists())->toBeTrue()
        ->and(QuoteRequestEntityMapping::where('quote_request_id', $newBusinessQuote->id)->value('entity_type_code'))->toBe('LLC');
});

it('copies CustomerMembers (UBO) to the new BusinessQuote on Corpline renewal', function () {
    $oldBusinessQuote = BusinessQuote::factory()->create([
        'source' => 'IMCRM',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    CustomerMembers::factory()->create([
        'quote_type' => BusinessQuote::class,
        'quote_id' => $oldBusinessQuote->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $newUuid = Str::uuid()->toString();
    $newCode = 'BUS-'.strtoupper(substr($newUuid, 0, 8));

    $oldQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $oldQuote->shouldReceive('getAttribute')->with('businessQuote')->andReturn($oldBusinessQuote);
    $oldQuote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($oldBusinessQuote);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $newQuote->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);
    $newQuote->shouldReceive('getAttribute')->with('quote_status_id')->andReturn(QuoteStatusEnum::NewLead);
    $newQuote->shouldReceive('getAttribute')->with('advisor_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('assignment_type')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('renewal_batch_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('uuid')->andReturn($newUuid);
    $newQuote->shouldReceive('getAttribute')->with('code')->andReturn($newCode);
    $newQuote->shouldReceive('businessQuote')->andReturn(
        Mockery::mock(BelongsTo::class)->shouldIgnoreMissing()
    );
    $newQuote->shouldReceive('save')->andReturnNull();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBusinessQuoteDetail($nq, $oq),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    $copyDetail($newQuote, $oldQuote);

    $newBusinessQuote = BusinessQuote::where('uuid', $newUuid)->first();

    $copiedMember = CustomerMembers::where('quote_id', $newBusinessQuote->id)
        ->where('quote_type', BusinessQuote::class)
        ->first();

    expect($copiedMember)->not->toBeNull()
        ->and($copiedMember->first_name)->toBe('John')
        ->and($copiedMember->last_name)->toBe('Doe');
});

it('skips QuoteRequestEntityMapping copy when old BusinessQuote has no entity mapping', function () {
    $oldBusinessQuote = BusinessQuote::factory()->create([
        'source' => 'IMCRM',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    $newUuid = Str::uuid()->toString();
    $newCode = 'BUS-'.strtoupper(substr($newUuid, 0, 8));

    $oldQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $oldQuote->shouldReceive('getAttribute')->with('businessQuote')->andReturn($oldBusinessQuote);
    $oldQuote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($oldBusinessQuote);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $newQuote->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);
    $newQuote->shouldReceive('getAttribute')->with('quote_status_id')->andReturn(QuoteStatusEnum::NewLead);
    $newQuote->shouldReceive('getAttribute')->with('advisor_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('assignment_type')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('renewal_batch_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('uuid')->andReturn($newUuid);
    $newQuote->shouldReceive('getAttribute')->with('code')->andReturn($newCode);
    $newQuote->shouldReceive('businessQuote')->andReturn(
        Mockery::mock(BelongsTo::class)->shouldIgnoreMissing()
    );
    $newQuote->shouldReceive('save')->andReturnNull();

    $countBefore = QuoteRequestEntityMapping::count();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBusinessQuoteDetail($nq, $oq),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    $copyDetail($newQuote, $oldQuote);

    expect(QuoteRequestEntityMapping::count())->toBe($countBefore);
});

it('returns false for shouldCopyInsured so insured names are not copied on Business renewal', function () {
    $shouldCopy = Closure::bind(
        fn () => $this->shouldCopyInsured(),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    expect($shouldCopy())->toBeFalse();
});

it('copies latestInsured trade license to new BusinessQuote on Group Medical renewal', function () {
    $oldBusinessQuote = BusinessQuote::factory()->groupMedical()->create([
        'customer_id' => 123,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    $insured = Insured::factory()->tradeLicense()->create([
        'id_number' => '784199510021234',
        'trade_license_no' => '784199510021234',
    ]);

    CustomerInsured::factory()->create([
        'quote_type_id' => QuoteTypeId::Business,
        'quote_request_id' => $oldBusinessQuote->id,
        'insured_id' => $insured->id,
        'customer_id' => 123,
        'is_active' => true,
    ]);

    $newUuid = Str::uuid()->toString();
    $newCode = 'BUS-'.strtoupper(substr($newUuid, 0, 8));

    $oldQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $oldQuote->shouldReceive('getAttribute')->with('businessQuote')->andReturn($oldBusinessQuote);
    $oldQuote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($oldBusinessQuote);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $newQuote->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);
    $newQuote->shouldReceive('getAttribute')->with('quote_status_id')->andReturn(QuoteStatusEnum::NewLead);
    $newQuote->shouldReceive('getAttribute')->with('advisor_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('assignment_type')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('renewal_batch_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('uuid')->andReturn($newUuid);
    $newQuote->shouldReceive('getAttribute')->with('code')->andReturn($newCode);
    $newQuote->shouldReceive('businessQuote')->andReturn(
        Mockery::mock(BelongsTo::class)->shouldIgnoreMissing()
    );
    $newQuote->shouldReceive('save')->andReturnNull();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBusinessQuoteDetail($nq, $oq),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    $copyDetail($newQuote, $oldQuote);

    $newBusinessQuote = BusinessQuote::where('uuid', $newUuid)->first();

    $copiedLink = CustomerInsured::where('quote_request_id', $newBusinessQuote->id)
        ->where('quote_type_id', QuoteTypeId::Business)
        ->where('is_active', true)
        ->first();

    expect($copiedLink)->not->toBeNull();

    $copiedInsured = Insured::find($copiedLink->insured_id);

    expect($copiedInsured->id_type)->toBe('tradeLicense')
        ->and($copiedInsured->id_number)->toBe('784199510021234')
        ->and($copiedInsured->trade_license_no)->toBe('784199510021234');
});

it('does not copy insured_kyc records when copying insured for Group Medical renewal', function () {
    $oldBusinessQuote = BusinessQuote::factory()->groupMedical()->create([
        'customer_id' => 123,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    $insured = Insured::factory()->tradeLicense()->create([
        'id_number' => '784199510021234',
        'trade_license_no' => '784199510021234',
    ]);

    InsuredKyc::create([
        'insured_id' => $insured->id,
        'trade_license_no' => '784199510021234',
        'first_name' => 'Acme',
    ]);

    CustomerInsured::factory()->create([
        'quote_type_id' => QuoteTypeId::Business,
        'quote_request_id' => $oldBusinessQuote->id,
        'insured_id' => $insured->id,
        'customer_id' => 123,
        'is_active' => true,
    ]);

    $newUuid = Str::uuid()->toString();
    $newCode = 'BUS-'.strtoupper(substr($newUuid, 0, 8));

    $oldQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $oldQuote->shouldReceive('getAttribute')->with('businessQuote')->andReturn($oldBusinessQuote);
    $oldQuote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($oldBusinessQuote);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $newQuote->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);
    $newQuote->shouldReceive('getAttribute')->with('quote_status_id')->andReturn(QuoteStatusEnum::NewLead);
    $newQuote->shouldReceive('getAttribute')->with('advisor_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('assignment_type')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('renewal_batch_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('uuid')->andReturn($newUuid);
    $newQuote->shouldReceive('getAttribute')->with('code')->andReturn($newCode);
    $newQuote->shouldReceive('businessQuote')->andReturn(
        Mockery::mock(BelongsTo::class)->shouldIgnoreMissing()
    );
    $newQuote->shouldReceive('save')->andReturnNull();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBusinessQuoteDetail($nq, $oq),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    $copyDetail($newQuote, $oldQuote);

    $newBusinessQuote = BusinessQuote::where('uuid', $newUuid)->first();
    $copiedLink = CustomerInsured::where('quote_request_id', $newBusinessQuote->id)->first();

    expect(InsuredKyc::where('insured_id', $copiedLink->insured_id)->exists())->toBeFalse();
});

it('copies latestInsured to new BusinessQuote on Corpline renewal', function () {
    $oldBusinessQuote = BusinessQuote::factory()->create([
        'customer_id' => 456,
        'business_type_of_insurance_id' => 16, // Trade Credit (Corpline)
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    $insured = Insured::factory()->create([
        'customer_type' => 'entity',
        'first_name' => 'Corp',
        'last_name' => 'Ltd',
    ]);

    CustomerInsured::factory()->create([
        'quote_type_id' => QuoteTypeId::Business,
        'quote_request_id' => $oldBusinessQuote->id,
        'insured_id' => $insured->id,
        'customer_id' => 456,
        'is_active' => true,
    ]);

    $newUuid = Str::uuid()->toString();
    $newCode = 'BUS-'.strtoupper(substr($newUuid, 0, 8));

    $oldQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $oldQuote->shouldReceive('getAttribute')->with('businessQuote')->andReturn($oldBusinessQuote);
    $oldQuote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($oldBusinessQuote);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $newQuote->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);
    $newQuote->shouldReceive('getAttribute')->with('quote_status_id')->andReturn(QuoteStatusEnum::NewLead);
    $newQuote->shouldReceive('getAttribute')->with('advisor_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('assignment_type')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('renewal_batch_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('uuid')->andReturn($newUuid);
    $newQuote->shouldReceive('getAttribute')->with('code')->andReturn($newCode);
    $newQuote->shouldReceive('businessQuote')->andReturn(
        Mockery::mock(BelongsTo::class)->shouldIgnoreMissing()
    );
    $newQuote->shouldReceive('save')->andReturnNull();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBusinessQuoteDetail($nq, $oq),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    $copyDetail($newQuote, $oldQuote);

    $newBusinessQuote = BusinessQuote::where('uuid', $newUuid)->first();

    $copiedLink = CustomerInsured::where('quote_request_id', $newBusinessQuote->id)
        ->where('quote_type_id', QuoteTypeId::Business)
        ->where('is_active', true)
        ->first();

    expect($copiedLink)->not->toBeNull();

    $copiedInsured = Insured::find($copiedLink->insured_id);

    expect($copiedInsured->customer_type)->toBe('entity')
        ->and($copiedInsured->first_name)->toBe('Corp')
        ->and($copiedInsured->last_name)->toBe('Ltd');
});

it('does not copy insured when old BusinessQuote has no latestInsured', function () {
    $oldBusinessQuote = BusinessQuote::factory()->create([
        'business_type_of_insurance_id' => null,
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
    ]);

    $newUuid = Str::uuid()->toString();
    $newCode = 'BUS-'.strtoupper(substr($newUuid, 0, 8));

    $oldQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $oldQuote->shouldReceive('getAttribute')->with('businessQuote')->andReturn($oldBusinessQuote);
    $oldQuote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($oldBusinessQuote);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $newQuote->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);
    $newQuote->shouldReceive('getAttribute')->with('quote_status_id')->andReturn(QuoteStatusEnum::NewLead);
    $newQuote->shouldReceive('getAttribute')->with('advisor_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('assignment_type')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('renewal_batch_id')->andReturn(null);
    $newQuote->shouldReceive('getAttribute')->with('uuid')->andReturn($newUuid);
    $newQuote->shouldReceive('getAttribute')->with('code')->andReturn($newCode);
    $newQuote->shouldReceive('businessQuote')->andReturn(
        Mockery::mock(BelongsTo::class)->shouldIgnoreMissing()
    );
    $newQuote->shouldReceive('save')->andReturnNull();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBusinessQuoteDetail($nq, $oq),
        $this->service,
        BusinessCQFQuoteStorageService::class
    );

    $copyDetail($newQuote, $oldQuote);

    $newBusinessQuote = BusinessQuote::where('uuid', $newUuid)->first();

    expect(CustomerInsured::where('quote_request_id', $newBusinessQuote->id)->exists())->toBeFalse();
});

afterEach(function () {
    Mockery::close();
});
