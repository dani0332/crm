<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\Customer;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
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
