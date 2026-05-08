<?php

declare(strict_types=1);

use App\Services\QuoteStatusLogService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

use function Pest\Laravel\mock;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

it('returns validation error when quote fields are missing and send update id is not provided', function () {
    $this->actingAs(TestDataSeeder::createAdminUser());

    $response = $this->getJson('/quotes/status-logs');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['quoteId', 'quoteTypeId']);
});

it('returns empty data when send update id does not exist', function () {
    $this->actingAs(TestDataSeeder::createAdminUser());
    mock(QuoteStatusLogService::class, function ($mock): void {
        $mock->shouldReceive('getQuoteStatusLogs')
            ->once()
            ->with(null, null, 999999)
            ->andReturn(new EloquentCollection);
    });

    $response = $this->getJson('/quotes/status-logs?sendUpdateId=999999');

    $response->assertSuccessful();
    expect($response->json())->toBe([]);
});
