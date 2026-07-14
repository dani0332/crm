<?php

use App\Services\BusinessQuoteService;

test('base query joins users as pqa_u only once', function () {
    $service = app(BusinessQuoteService::class);

    $property = new ReflectionProperty(BusinessQuoteService::class, 'query');
    $property->setAccessible(true);
    $query = $property->getValue($service);

    $pqaUserJoins = collect($query->joins)
        ->filter(fn ($join) => $join->table === 'users as pqa_u')
        ->count();

    expect($pqaUserJoins)->toBe(1);
});
