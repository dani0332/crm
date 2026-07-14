<?php

declare(strict_types=1);

use App\Models\PersonalQuote;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('has a previousAdvisor relationship defined as BelongsTo', function () {
    $quote = new PersonalQuote;
    $relation = $quote->previousAdvisor();

    expect($relation)->toBeInstanceOf(BelongsTo::class)
        ->and($relation->getForeignKeyName())->toBe('previous_advisor_id')
        ->and($relation->getRelated())->toBeInstanceOf(User::class);
});

it('resolves previousAdvisor name via eager loading', function () {
    $user = Mockery::mock(User::class)->shouldIgnoreMissing();
    $user->shouldReceive('getAttribute')->with('name')->andReturn('Test Advisor');
    $user->name = 'Test Advisor';

    $quote = new PersonalQuote;
    $quote->setRelation('previousAdvisor', $user);

    expect($quote->previousAdvisor->name)->toBe('Test Advisor');
});

it('returns null when previous_advisor_id is not set', function () {
    $quote = new PersonalQuote;
    $quote->setRelation('previousAdvisor', null);

    expect($quote->previousAdvisor)->toBeNull();
    expect($quote->previousAdvisor?->name)->toBeNull();
});

afterEach(function () {
    Mockery::close();
});
