<?php

use App\Services\BusinessQuoteService;
use Illuminate\Http\Request;
use Tests\Helpers\TestDataSeeder;

test('advisor_id array filter is scoped to the bqr table to avoid an ambiguous column error', function () {
    $user = TestDataSeeder::createUser();
    $this->actingAs($user);

    $service = app(BusinessQuoteService::class);

    $model = new stdClass;
    $model->searchProperties = $service->fillModelSearchProperties();

    $request = Request::create('/', 'GET', ['advisor_id' => [101, 102]]);

    $service->getGridData($model, $request);

    $property = new ReflectionProperty(BusinessQuoteService::class, 'query');
    $property->setAccessible(true);
    $query = $property->getValue($service);

    $wheres = collect($query->wheres);

    expect($wheres->contains(fn ($where) => ($where['column'] ?? null) === 'bqr.advisor_id' && $where['type'] === 'In'))->toBeTrue()
        ->and($wheres->contains(fn ($where) => ($where['column'] ?? null) === 'advisor_id'))->toBeFalse();
});
