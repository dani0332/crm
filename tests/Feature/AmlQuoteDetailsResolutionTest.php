<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\CarQuote;
use App\Models\QuoteType;
use App\Services\AML\AMLQuoteDetailsService;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
    $this->withoutMiddleware(HandleInertiaRequests::class);

    QuoteType::factory()->createForSqlite(['id' => 1, 'code' => 'Car', 'text' => 'Car Insurance']);

    $this->service = app(AMLQuoteDetailsService::class);
});

afterEach(function () {
    Mockery::close();
});

// ──────────────────────────────────────────────────────────────────────────────
// resolveQuoteRequestId — unit-level tests on the service method directly
// ──────────────────────────────────────────────────────────────────────────────

it('resolves a quote code to the integer id via the service method', function () {
    $carQuote = CarQuote::factory()->create(['code' => 'RENEWALCD']);

    expect($this->service->resolveQuoteRequestId(1, 'RENEWALCD'))->toBe($carQuote->id);
});

it('extracts the leading numeric portion from a noisy id like 1027818*-', function () {
    expect($this->service->resolveQuoteRequestId(1, '1027818*-'))->toBe(1027818);
});

it('passes a numeric string through unchanged as integer', function () {
    expect($this->service->resolveQuoteRequestId(1, '123456'))->toBe(123456);
});

// ──────────────────────────────────────────────────────────────────────────────
// amlQuoteDetails controller — verifies it delegates resolution to the service
// ──────────────────────────────────────────────────────────────────────────────

it('controller delegates id resolution and data preparation to AMLQuoteDetailsService', function () {
    $capturedArgs = [];

    $serviceMock = Mockery::mock(AMLQuoteDetailsService::class);
    $serviceMock->shouldReceive('resolveQuoteRequestId')
        ->once()
        ->with(1, 'RENEWALCD')
        ->andReturn(99);
    $serviceMock->shouldReceive('prepareQuoteDetailsData')
        ->once()
        ->withArgs(function (int $typeId, int $requestId) use (&$capturedArgs) {
            $capturedArgs = [$typeId, $requestId];

            return true;
        })
        ->andReturn([]);
    $this->app->instance(AMLQuoteDetailsService::class, $serviceMock);

    $this->get('/kyc/aml/1/details/RENEWALCD', ['X-Inertia' => 'true'])
        ->assertStatus(200);

    expect($capturedArgs[0])->toBe(1)
        ->and($capturedArgs[1])->toBe(99);
});

it('returns 404 when the quote code cannot be resolved to any quote', function () {
    $serviceMock = Mockery::mock(AMLQuoteDetailsService::class);
    $serviceMock->shouldReceive('resolveQuoteRequestId')
        ->once()
        ->with(1, 'NOTEXIST')
        ->andReturn(0);
    $serviceMock->shouldNotReceive('prepareQuoteDetailsData');
    $this->app->instance(AMLQuoteDetailsService::class, $serviceMock);

    $this->get('/kyc/aml/1/details/NOTEXIST', ['X-Inertia' => 'true'])
        ->assertStatus(404);
});
