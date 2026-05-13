<?php

declare(strict_types=1);

use App\Services\QuoteStatusLogService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();
});

it('returns validation error when quote fields are missing', function (): void {
    $this->actingAs(TestDataSeeder::createAdminUser());

    $response = $this->getJson('/quotes/status-logs');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['quoteId', 'quoteTypeId']);
});

it('casts string query parameters to integers for the status log service', function (): void {
    $this->mock(QuoteStatusLogService::class, function ($mock): void {
        $mock->shouldReceive('getQuoteStatusLogs')
            ->once()
            ->with(1, 241599)
            ->andReturn(new EloquentCollection);
    });

    $this->actingAs(TestDataSeeder::createAdminUser());

    $this->getJson('/quotes/status-logs?quoteId=241599&quoteTypeId=1')
        ->assertSuccessful();
});
