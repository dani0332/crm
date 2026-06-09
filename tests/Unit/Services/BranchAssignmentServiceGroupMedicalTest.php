<?php

use App\Enums\EmirateEnum;
use App\Enums\QuoteTypeId;
use App\Models\BusinessQuote;
use App\Models\User;
use App\Services\BranchAssignmentService;
use Illuminate\Support\Collection;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();

    // Seed the private static caches directly so the constructor never queries the DB.
    $ref = new ReflectionClass(BranchAssignmentService::class);

    $branchProp = $ref->getProperty('branches');
    $branchProp->setAccessible(true);
    $branchProp->setValue(null, new Collection);

    $configProp = $ref->getProperty('branchOverrideConfigs');
    $configProp->setAccessible(true);
    $configProp->setValue(null, new Collection);

    $this->service = new BranchAssignmentService;
});

afterEach(function () {
    Mockery::close();
});

function advisorMockForBranch(bool $hasBranch): User
{
    $advisor = Mockery::mock(User::class)->makePartial();
    $relation = Mockery::mock();
    $relation->shouldReceive('exists')->andReturn($hasBranch);
    $advisor->shouldReceive('primaryBranch')->andReturn($relation);

    return $advisor;
}

// Commit ecb530975: GM reads emirate_of_registration_id directly from the quote, not latestInsured

test('hasBranchAssignment returns true when emirate_of_registration_id is set directly on the GM quote', function () {
    $quote = BusinessQuote::factory()->groupMedical()->withEmirate(EmirateEnum::ABU_DHABI)->make();
    $quote->setRelation('advisor', advisorMockForBranch(true));

    expect($this->service->hasBranchAssignment($quote, QuoteTypeId::GroupMedical))->toBeTrue();
});

test('hasBranchAssignment returns false when emirate_of_registration_id is null on the GM quote', function () {
    $quote = BusinessQuote::factory()->groupMedical()->make(['emirate_of_registration_id' => null]);
    $quote->setRelation('advisor', advisorMockForBranch(true));

    expect($this->service->hasBranchAssignment($quote, QuoteTypeId::GroupMedical))->toBeFalse();
});

test('hasBranchAssignment does not use latestInsured for emirate resolution on a GM quote', function () {
    $quote = BusinessQuote::factory()->groupMedical()->withEmirate(EmirateEnum::DUBAI)->make();
    // latestInsured would previously have been the source — it is now ignored
    $quote->latestInsured = (object) ['emirate_of_registration_id' => null];
    $quote->setRelation('advisor', advisorMockForBranch(true));

    // emirate comes from the quote directly (Dubai), so assignment is valid
    expect($this->service->hasBranchAssignment($quote, QuoteTypeId::GroupMedical))->toBeTrue();
});
