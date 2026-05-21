<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Strategies\Allocations\BaseAllocation;
use App\Strategies\Allocations\LifeAllocation;
use App\Strategies\Allocations\YachtAllocation;

$callVerify = function (BaseAllocation $allocation, object $lead): bool {
    Closure::bind(function () use ($lead): void {
        $this->lead = $lead;
    }, $allocation, BaseAllocation::class)();

    return Closure::bind(
        fn () => $this->verifyLeadPreChecks(),
        $allocation,
        BaseAllocation::class
    )();
};

it('blocks a supported LOB lead sourced from renewal upload', function () use ($callVerify) {
    $allocation = new YachtAllocation(QuoteTypes::YACHT, 'uuid-1');
    $lead = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $lead->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);

    expect($callVerify($allocation, $lead))->toBeFalse();
});

it('allows a supported LOB lead with a non-renewal source', function () use ($callVerify) {
    $allocation = new YachtAllocation(QuoteTypes::YACHT, 'uuid-2');
    $lead = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $lead->shouldReceive('getAttribute')->with('source')->andReturn('NewBusiness');

    expect($callVerify($allocation, $lead))->toBeTrue();
});

it('allows an unsupported LOB lead even when sourced from renewal upload', function () use ($callVerify) {
    $allocation = new LifeAllocation(QuoteTypes::LIFE, 'uuid-3');
    $lead = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $lead->shouldReceive('getAttribute')->with('source')->andReturn(LeadSourceEnum::RENEWAL_UPLOAD);

    expect($callVerify($allocation, $lead))->toBeTrue();
});

afterEach(function () {
    Mockery::close();
});
