<?php

use App\Enums\BranchEnum;
use App\Enums\EmirateEnum;
use App\Models\Branch;
use App\Models\BusinessQuote;
use App\Services\BranchAssignmentService;
use App\Traits\GenericQueriesAllLobs;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();

    $this->subject = new class
    {
        use GenericQueriesAllLobs;
    };
});

afterEach(function () {
    Mockery::close();
});

function abuDhabiBranchStub(int $id): Branch
{
    $branch = new Branch;
    $branch->id = $id;

    return $branch;
}

function mockBranchServiceForIsAbuDhabi(Branch $branch): void
{
    $mock = Mockery::mock(BranchAssignmentService::class)->makePartial();
    $mock->shouldReceive('getBranch')->andReturn($branch);
    app()->instance(BranchAssignmentService::class, $mock);
}

// Commit ecb530975: isAbuDhabiBranch reads emirate_of_registration_id directly from record, not latestInsured

test('isAbuDhabiBranch resolves Abu Dhabi when emirate_of_registration_id is set directly on the GM quote', function () {
    mockBranchServiceForIsAbuDhabi(abuDhabiBranchStub(BranchEnum::ABU_DHABI->value));

    $quote = BusinessQuote::factory()->groupMedicalAbuDhabi()->branchApplicable()->make(['advisor_id' => null]);

    expect($this->subject->isAbuDhabiBranch('Group Medical', $quote))->toBeTrue();
});

test('isAbuDhabiBranch resolves non-Abu-Dhabi when emirate_of_registration_id is Dubai on the GM quote', function () {
    mockBranchServiceForIsAbuDhabi(abuDhabiBranchStub(BranchEnum::DUBAI->value));

    $quote = BusinessQuote::factory()->groupMedical()->withEmirate(EmirateEnum::DUBAI)->branchApplicable()->make(['advisor_id' => null]);

    expect($this->subject->isAbuDhabiBranch('Group Medical', $quote))->toBeFalse();
});

test('isAbuDhabiBranch does not use latestInsured for emirate resolution on a GM quote', function () {
    mockBranchServiceForIsAbuDhabi(abuDhabiBranchStub(BranchEnum::ABU_DHABI->value));

    $quote = BusinessQuote::factory()->groupMedicalAbuDhabi()->branchApplicable()->make(['advisor_id' => null]);
    // latestInsured would previously have been the source — it must now be ignored
    $quote->latestInsured = (object) ['emirate_of_registration_id' => null];

    expect($this->subject->isAbuDhabiBranch('Group Medical', $quote))->toBeTrue();
});
