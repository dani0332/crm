<?php

use App\Builders\HealthQuoteQueryBuilder;
use App\Enums\QuoteStatusEnum;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('does not apply the fake quote exclusion when a quote status filter is provided', function () {
    Auth::shouldReceive('check')->andReturn(true);

    $authUser = Mockery::mock(User::class);
    $authUser->shouldReceive('isSpecificTeamAdvisor')->andReturn(false);
    $authUser->shouldReceive('can')->andReturn(false);
    Auth::shouldReceive('user')->andReturn($authUser);

    $queryBuilder = new HealthQuoteQueryBuilder;
    $query = $queryBuilder->buildGrid();
    $queryBuilder->applyFilters($query, ['quote_status' => [QuoteStatusEnum::Quoted]]);

    $notInFilter = collect($query->getQuery()->wheres)->first(
        fn ($where) => $where['type'] === 'NotIn' && $where['column'] === 'quote_status_id'
    );

    // Assert that the fake-status filter is not injected when the user explicitly requested specific statuses.
    expect($notInFilter)->toBeNull();
});

it('filters only assigned leads when unassigned filter is no', function () {
    Auth::shouldReceive('check')->andReturn(true);

    $authUser = Mockery::mock(User::class);
    $authUser->shouldReceive('isSpecificTeamAdvisor')->andReturn(false);
    $authUser->shouldReceive('can')->andReturn(false);
    Auth::shouldReceive('user')->andReturn($authUser);

    $queryBuilder = new HealthQuoteQueryBuilder;
    $query = $queryBuilder->buildGrid();
    $queryBuilder->applyFilters($query, ['unassigned' => 'no']);

    $advisorNotNullFilter = collect($query->getQuery()->wheres)->first(
        fn ($where) => $where['type'] === 'NotNull' && $where['column'] === 'advisor_id'
    );

    expect($advisorNotNullFilter)->not->toBeNull();
});

it('filters unassigned leads and excludes sic1 leads when unassigned filter is yes', function () {
    Auth::shouldReceive('check')->andReturn(true);

    $authUser = Mockery::mock(User::class);
    $authUser->shouldReceive('isSpecificTeamAdvisor')->andReturn(false);
    $authUser->shouldReceive('can')->andReturn(false);
    Auth::shouldReceive('user')->andReturn($authUser);

    $queryBuilder = new HealthQuoteQueryBuilder;
    $query = $queryBuilder->buildGrid();
    $queryBuilder->applyFilters($query, ['unassigned' => 'yes']);

    $advisorNullFilter = collect($query->getQuery()->wheres)->first(
        fn ($where) => $where['type'] === 'Null' && $where['column'] === 'advisor_id'
    );
    $healthPlanNotNullFilter = collect($query->getQuery()->wheres)->first(
        fn ($where) => $where['type'] === 'NotNull' && $where['column'] === 'health_plan_type_id'
    );

    expect($advisorNullFilter)->not->toBeNull()
        ->and($healthPlanNotNullFilter)->not->toBeNull();
});

it('filters leads with customer members age sixty and above when filter is yes', function () {
    Auth::shouldReceive('check')->andReturn(true);

    $authUser = Mockery::mock(User::class);
    $authUser->shouldReceive('isSpecificTeamAdvisor')->andReturn(false);
    $authUser->shouldReceive('can')->andReturn(false);
    Auth::shouldReceive('user')->andReturn($authUser);

    $queryBuilder = new HealthQuoteQueryBuilder;
    $query = $queryBuilder->buildGrid();
    $queryBuilder->applyFilters($query, ['age_sixty_and_above' => 'yes']);

    $existsFilter = collect($query->getQuery()->wheres)->first(
        fn ($where) => $where['type'] === 'Exists'
    );

    expect($existsFilter)->not->toBeNull();
});

it('filters leads without customer members age sixty and above when filter is no', function () {
    Auth::shouldReceive('check')->andReturn(true);

    $authUser = Mockery::mock(User::class);
    $authUser->shouldReceive('isSpecificTeamAdvisor')->andReturn(false);
    $authUser->shouldReceive('can')->andReturn(false);
    Auth::shouldReceive('user')->andReturn($authUser);

    $queryBuilder = new HealthQuoteQueryBuilder;
    $query = $queryBuilder->buildGrid();
    $queryBuilder->applyFilters($query, ['age_sixty_and_above' => 'no']);

    $notExistsFilter = collect($query->getQuery()->wheres)->first(
        fn ($where) => $where['type'] === 'NotExists'
    );

    expect($notExistsFilter)->not->toBeNull();
});
