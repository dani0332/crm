<?php

use App\Builders\HealthQuoteQueryBuilder;
use App\Enums\QuoteStatusEnum;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Mockery;

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
