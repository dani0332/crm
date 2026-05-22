<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\BikeQuote;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\PersonalQuote;
use App\Models\UAELicenseHeldFor;
use App\Services\CQF\NonMotor\LOBs\BikeCQFQuoteStorageService;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
    $this->service = app(BikeCQFQuoteStorageService::class);
});

it('increments uae_license_held_for_id to the next active entry', function () {
    $current = UAELicenseHeldFor::factory()->create(['text' => '1 year']);
    $next = UAELicenseHeldFor::factory()->create(['text' => '2 years']);

    $increment = Closure::bind(
        fn ($id) => $this->incrementLicenseHeldForId($id),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    expect($increment($current->id))->toBe($next->id);
});

it('keeps uae_license_held_for_id at cap when already at the highest active entry', function () {
    $cap = UAELicenseHeldFor::factory()->create(['text' => '5 years and above']);

    $increment = Closure::bind(
        fn ($id) => $this->incrementLicenseHeldForId($id),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    expect($increment($cap->id))->toBe($cap->id);
});

it('skips inactive entries when incrementing uae_license_held_for_id', function () {
    $current = UAELicenseHeldFor::factory()->create(['text' => '4 years']);
    UAELicenseHeldFor::factory()->inactive()->create(['text' => 'deleted entry']);
    $active = UAELicenseHeldFor::factory()->create(['text' => '5 years and above']);

    $increment = Closure::bind(
        fn ($id) => $this->incrementLicenseHeldForId($id),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    expect($increment($current->id))->toBe($active->id);
});

it('returns null when incrementing a null uae_license_held_for_id', function () {
    $increment = Closure::bind(
        fn ($id) => $this->incrementLicenseHeldForId($id),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    expect($increment(null))->toBeNull();
});

it('stores copied BikeQuote with renewal source and new lead status from PersonalQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Bike]);

    BikeQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'source' => 'direct',
        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
        'advisor_id' => 99,
    ]);

    $oldPq->load('bikeQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Bike,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'advisor_id' => null,
        'code' => 'BIK-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBikeQuoteDetail($nq, $oq),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $created = BikeQuote::where('personal_quote_id', $newPq->id)->first();

    expect($created)->not->toBeNull()
        ->and($created->source)->toBe(LeadSourceEnum::RENEWAL_UPLOAD)
        ->and($created->quote_status_id)->toBe(QuoteStatusEnum::NewLead)
        ->and($created->advisor_id)->toBeNull();
});

it('sets previous_quote_id to the old BikeQuote id on copy', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Bike]);

    $oldBikeQuote = BikeQuote::factory()->create(['personal_quote_id' => $oldPq->id]);

    $oldPq->load('bikeQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Bike,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'BIK-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBikeQuoteDetail($nq, $oq),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(BikeQuote::where('personal_quote_id', $newPq->id)->value('previous_quote_id'))
        ->toBe($oldBikeQuote->id);
});

it('leaves bike_value, claim_history_id, and premium null on copied BikeQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Bike]);

    BikeQuote::factory()->create(['personal_quote_id' => $oldPq->id]);

    $oldPq->load('bikeQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Bike,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'BIK-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBikeQuoteDetail($nq, $oq),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $created = BikeQuote::where('personal_quote_id', $newPq->id)->first();

    expect($created->bike_value)->toBeNull()
        ->and($created->claim_history_id)->toBeNull()
        ->and($created->has_ncd_supporting_documents)->toBeNull()
        ->and($created->premium)->toBeNull();
});

it('carries insurance_type_id from old BikeQuote on renewal copy', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Bike]);

    BikeQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'insurance_type_id' => 2,
    ]);

    $oldPq->load('bikeQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Bike,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'BIK-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBikeQuoteDetail($nq, $oq),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(BikeQuote::where('personal_quote_id', $newPq->id)->value('insurance_type_id'))->toBe(2);
});

it('carries bike make, model, and chassis from old BikeQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Bike]);

    BikeQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'chassis_number' => 'ABC123',
        'year_of_manufacture' => 2020,
    ]);

    $oldPq->load('bikeQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Bike,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'BIK-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBikeQuoteDetail($nq, $oq),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    $created = BikeQuote::where('personal_quote_id', $newPq->id)->first();

    expect($created->chassis_number)->toBe('ABC123')
        ->and($created->year_of_manufacture)->toBe('2020');
});

it('skips copying when old PersonalQuote has no BikeQuote', function () {
    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Bike]);
    $newPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Bike]);

    $countBefore = BikeQuote::count();

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBikeQuoteDetail($nq, $oq),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(BikeQuote::count())->toBe($countBefore);
});

it('decrements uae_license_held_for_id to the previous active entry', function () {
    $prev = UAELicenseHeldFor::factory()->create(['text' => '1 year']);
    $current = UAELicenseHeldFor::factory()->create(['text' => '2 years']);

    $decrement = Closure::bind(
        fn ($id) => $this->decrementLicenseHeldForId($id),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    expect($decrement($current->id))->toBe($prev->id);
});

it('keeps uae_license_held_for_id unchanged when already at the lowest active entry', function () {
    $lowest = UAELicenseHeldFor::factory()->create(['text' => 'Less than 1 year']);

    $decrement = Closure::bind(
        fn ($id) => $this->decrementLicenseHeldForId($id),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    expect($decrement($lowest->id))->toBe($lowest->id);
});

it('returns null when decrementing a null uae_license_held_for_id', function () {
    $decrement = Closure::bind(
        fn ($id) => $this->decrementLicenseHeldForId($id),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    expect($decrement(null))->toBeNull();
});

it('decrements uae_license_held_for_id on Bike-to-Bike renewal', function () {
    $prev = UAELicenseHeldFor::factory()->create(['text' => '1 year']);
    $current = UAELicenseHeldFor::factory()->create(['text' => '2 years']);

    $oldPq = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Bike]);

    BikeQuote::factory()->create([
        'personal_quote_id' => $oldPq->id,
        'uae_license_held_for_id' => $current->id,
    ]);

    $oldPq->load('bikeQuote');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Bike,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'BIK-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyDetail = Closure::bind(
        fn ($nq, $oq) => $this->copyBikeQuoteDetail($nq, $oq),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    $copyDetail($newPq, $oldPq);

    expect(BikeQuote::where('personal_quote_id', $newPq->id)->value('uae_license_held_for_id'))
        ->toBe($prev->id);
});

it('increments uae_license_held_for_id on Car-to-Bike renewal', function () {
    $current = UAELicenseHeldFor::factory()->create(['text' => '1 year']);
    $next = UAELicenseHeldFor::factory()->create(['text' => '2 years']);

    $carQuote = CarQuote::factory()->create([
        'uae_license_held_for_id' => $current->id,
    ]);

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Bike,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'BIK-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyCarToBike = Closure::bind(
        fn ($nq, $cq) => $this->copyCarQuoteToBikeQuoteDetail($nq, $cq),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    $copyCarToBike($newPq, $carQuote);

    expect(BikeQuote::where('personal_quote_id', $newPq->id)->value('uae_license_held_for_id'))
        ->toBe($next->id);
});

it('copies chassis_number from car_quote_request_detail on Car-to-Bike renewal', function () {
    $carQuote = CarQuote::factory()->create();

    CarQuoteRequestDetail::factory()->forCarQuote($carQuote)->create([
        'chassis_number' => 'VIN123456789',
    ]);

    $carQuote->load('carQuoteRequestDetail');

    $newPq = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Bike,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'code' => 'BIK-NEW-'.Str::upper(Str::random(4)),
    ]);

    $copyCarToBike = Closure::bind(
        fn ($nq, $cq) => $this->copyCarQuoteToBikeQuoteDetail($nq, $cq),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    $copyCarToBike($newPq, $carQuote);

    expect(BikeQuote::where('personal_quote_id', $newPq->id)->value('chassis_number'))
        ->toBe('VIN123456789');
});
