<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\Customer;
use App\Models\CustomerInsured;
use App\Models\InsuranceProvider;
use App\Models\Insured;
use App\Models\Lookup;
use App\Models\Nationality;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\CQF\NonMotor\LOBs\PetCQFQuoteStorageService;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
    $this->service = app(PetCQFQuoteStorageService::class);
});

it('stores copied PetQuote with renewal source and new lead status from PersonalQuote', function () {
    $insurer = InsuranceProvider::factory()->create();
    $customer = Customer::factory()->create();
    $nationality = Nationality::factory()->create();

    $oldPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
        'insurance_provider_id' => $insurer->id,
    ]);

    PetQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'uuid' => $oldPq->uuid,
        'code' => $oldPq->code,
        'first_name' => 'Old',
        'last_name' => 'Pet',
        'email' => 'old-pet-'.Str::random(8).'@example.com',
        'source' => 'IMCRM',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'advisor_id' => 99,
    ]);

    $oldPq->load('petQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
        'insurance_provider_id' => $insurer->id,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'advisor_id' => null,
        'code' => 'PET-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyPetQuoteDetail($nq, $oq),
        $this->service,
        PetCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $createdPetQuote = PetQuote::where('personal_quote_id', $newPq->id)->first();

    expect($createdPetQuote)->not->toBeNull()
        ->and($createdPetQuote->source)->toBe(LeadSourceEnum::RENEWAL_UPLOAD)
        ->and($createdPetQuote->quote_status_id)->toBe(QuoteStatusEnum::NewLead)
        ->and($createdPetQuote->advisor_id)->toBeNull();
});

it('does not keep old PetQuote source when parent had IMCRM', function () {
    $insurer = InsuranceProvider::factory()->create();
    $customer = Customer::factory()->create();
    $nationality = Nationality::factory()->create();

    $oldPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
        'insurance_provider_id' => $insurer->id,
    ]);

    PetQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'uuid' => $oldPq->uuid,
        'code' => $oldPq->code,
        'first_name' => 'Old',
        'last_name' => 'Pet',
        'email' => 'old-pet-2-'.Str::random(8).'@example.com',
        'source' => 'direct',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'advisor_id' => 7,
    ]);

    $oldPq->load('petQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
        'insurance_provider_id' => $insurer->id,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'advisor_id' => null,
        'code' => 'PET-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyPetQuoteDetail($nq, $oq),
        $this->service,
        PetCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $createdPetQuote = PetQuote::where('personal_quote_id', $newPq->id)->first();

    expect($createdPetQuote->source)->not->toBe('direct')
        ->and($createdPetQuote->source)->toBe(LeadSourceEnum::RENEWAL_UPLOAD);
});

it('stores copied PetQuote renewal_batch_id from new PersonalQuote, not the old pet row', function () {
    $insurer = InsuranceProvider::factory()->create();
    $customer = Customer::factory()->create();
    $nationality = Nationality::factory()->create();

    $oldPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
        'insurance_provider_id' => $insurer->id,
        'renewal_batch_id' => 100,
    ]);

    PetQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'uuid' => $oldPq->uuid,
        'code' => $oldPq->code,
        'first_name' => 'Old',
        'last_name' => 'Pet',
        'email' => 'old-pet-rb-'.Str::random(8).'@example.com',
        'source' => 'IMCRM',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'advisor_id' => 1,
        'renewal_batch_id' => 999,
    ]);

    $oldPq->load('petQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
        'insurance_provider_id' => $insurer->id,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'advisor_id' => null,
        'renewal_batch_id' => 42,
        'code' => 'PET-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyPetQuoteDetail($nq, $oq),
        $this->service,
        PetCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $createdPetQuote = PetQuote::where('personal_quote_id', $newPq->id)->first();

    expect($createdPetQuote)->not->toBeNull()
        ->and($createdPetQuote->renewal_batch_id)->toBe(42);
});

it('increments pet_age_id to the next pet-ages lookup entry on copy', function () {
    $age1 = Lookup::factory()->create(['key' => 'pet-ages', 'text' => '1 Year old', 'code' => 'test']);
    $age2 = Lookup::factory()->create(['key' => 'pet-ages', 'text' => '2 Year old', 'code' => 'test']);

    $customer = Customer::factory()->create();
    $nationality = Nationality::factory()->create();

    $oldPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
    ]);

    PetQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'uuid' => $oldPq->uuid,
        'code' => $oldPq->code,
        'email' => 'pet-age-inc-'.Str::random(8).'@example.com',
        'pet_age_id' => $age1->id,
    ]);

    $oldPq->load('petQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'advisor_id' => null,
        'code' => 'PET-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyPetQuoteDetail($nq, $oq),
        $this->service,
        PetCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(PetQuote::where('personal_quote_id', $newPq->id)->value('pet_age_id'))->toBe($age2->id);
});

it('keeps pet_age_id at cap when already at the highest pet-ages entry', function () {
    $cap = Lookup::factory()->create(['key' => 'pet-ages', 'text' => '10 Year old', 'code' => 'test']);

    $customer = Customer::factory()->create();
    $nationality = Nationality::factory()->create();

    $oldPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
    ]);

    PetQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'uuid' => $oldPq->uuid,
        'code' => $oldPq->code,
        'email' => 'pet-age-cap-'.Str::random(8).'@example.com',
        'pet_age_id' => $cap->id,
    ]);

    $oldPq->load('petQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'advisor_id' => null,
        'code' => 'PET-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyPetQuoteDetail($nq, $oq),
        $this->service,
        PetCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(PetQuote::where('personal_quote_id', $newPq->id)->value('pet_age_id'))->toBe($cap->id);
});

it('copies customer_insured record from old quote to renewal quote', function () {
    $customer = Customer::factory()->create();
    $insured = Insured::create(['customer_type' => 'Individual', 'first_name' => 'Test', 'last_name' => 'User']);

    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => 9, 'customer_id' => $customer->id]);
    $newPq = PersonalQuote::factory()->create(['quote_type_id' => 9, 'customer_id' => $customer->id]);

    CustomerInsured::create([
        'quote_type_id' => 9,
        'quote_request_id' => $oldPq->id,
        'insured_id' => $insured->id,
        'customer_id' => $customer->id,
        'is_active' => true,
    ]);

    $copyFn = Closure::bind(
        fn ($old, $new) => $this->copyCustomerInsured($old, $new),
        $this->service,
        BaseCQFQuoteStorageService::class
    );

    $copyFn($oldPq, $newPq);

    $newRecord = CustomerInsured::where('quote_request_id', $newPq->id)->where('is_active', true)->first();

    expect($newRecord)->not->toBeNull()
        ->and($newRecord->insured_id)->toBe($insured->id)
        ->and($newRecord->customer_id)->toBe($customer->id)
        ->and($newRecord->quote_type_id)->toBe(9);
});

it('skips customer_insured copy when old quote has no customer_insured record', function () {
    $customer = Customer::factory()->create();

    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => 9, 'customer_id' => $customer->id]);
    $newPq = PersonalQuote::factory()->create(['quote_type_id' => 9, 'customer_id' => $customer->id]);

    $copyFn = Closure::bind(
        fn ($old, $new) => $this->copyCustomerInsured($old, $new),
        $this->service,
        BaseCQFQuoteStorageService::class
    );

    $copyFn($oldPq, $newPq);

    expect(CustomerInsured::where('quote_request_id', $newPq->id)->count())->toBe(0);
});

it('sets previous_quote_id to the old PetQuote id on copy', function () {
    $customer = Customer::factory()->create();
    $nationality = Nationality::factory()->create();

    $oldPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
    ]);

    $oldPetQuote = PetQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'uuid' => $oldPq->uuid,
        'code' => $oldPq->code,
        'email' => 'pet-prev-'.Str::random(8).'@example.com',
    ]);

    $oldPq->load('petQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'PET-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyPetQuoteDetail($nq, $oq),
        $this->service,
        PetCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(PetQuote::where('personal_quote_id', $newPq->id)->value('previous_quote_id'))
        ->toBe($oldPetQuote->id);
});

it('leaves premium and policy_number null on copied PetQuote', function () {
    $customer = Customer::factory()->create();
    $nationality = Nationality::factory()->create();

    $oldPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
    ]);

    PetQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'uuid' => $oldPq->uuid,
        'code' => $oldPq->code,
        'email' => 'pet-null-'.Str::random(8).'@example.com',
    ]);

    $oldPq->load('petQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'PET-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyPetQuoteDetail($nq, $oq),
        $this->service,
        PetCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $created = PetQuote::where('personal_quote_id', $newPq->id)->first();

    expect($created->premium)->toBeNull()
        ->and($created->policy_number)->toBeNull()
        ->and($created->insurance_provider_id)->toBeNull();
});

it('carries personal detail fields from old PetQuote', function () {
    $customer = Customer::factory()->create();
    $nationality = Nationality::factory()->create();

    $oldPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
    ]);

    PetQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'uuid' => $oldPq->uuid,
        'code' => $oldPq->code,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'pet-carry-'.Str::random(8).'@example.com',
        'mobile_no' => '0501234567',
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
    ]);

    $oldPq->load('petQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'PET-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyPetQuoteDetail($nq, $oq),
        $this->service,
        PetCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $created = PetQuote::where('personal_quote_id', $newPq->id)->first();

    expect($created->first_name)->toBe('Jane')
        ->and($created->last_name)->toBe('Doe')
        ->and($created->mobile_no)->toBe('0501234567')
        ->and($created->customer_id)->toBe($customer->id)
        ->and($created->nationality_id)->toBe($nationality->id);
});

it('skips copying when old quote has no PetQuote detail', function () {
    $oldPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
    ]);

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
    ]);

    $countBefore = PetQuote::count();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyPetQuoteDetail($nq, $oq),
        $this->service,
        PetCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(PetQuote::count())->toBe($countBefore);
});
