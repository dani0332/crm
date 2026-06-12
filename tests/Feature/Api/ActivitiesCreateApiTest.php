<?php

declare(strict_types=1);

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\QuoteType;
use App\Models\User;
use App\Services\ActivitiesService;
use App\Services\CRUDService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('createActivityApi returns 404 when entity is not found for UUID and quote type', function () {
    QuoteType::query()->forceCreate([
        'id' => QuoteTypeId::Car,
        'code' => quoteTypeCode::Car,
        'text' => 'Car',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    User::factory()->create([
        'email' => 'system@insurancemarket.ae',
    ]);

    $this->mock(CRUDService::class, function ($mock) {
        $mock->shouldReceive('getEntity')
            ->once()
            ->with(quoteTypeCode::Car, 'non-existent-entity-uuid')
            ->andReturn(null);
    });

    $response = app(ActivitiesService::class)->createActivityApi(
        'non-existent-entity-uuid',
        QuoteTypeId::Car,
        'CALL_BACK',
        'Title',
        'Description',
        now()->toDateTimeString()
    );

    expect($response->getStatusCode())->toBe(404)
        ->and($response->getData(true)['message'] ?? '')
        ->toContain('No quote or entity found');
});

test('createActivityApi returns 422 when quote type id is invalid', function () {
    $response = app(ActivitiesService::class)->createActivityApi(
        'any-uuid',
        999_999_999,
        'CALL_BACK',
        'Title',
        'Description',
        now()->toDateTimeString()
    );

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true)['message'] ?? '')
        ->toContain('quote type');
});
