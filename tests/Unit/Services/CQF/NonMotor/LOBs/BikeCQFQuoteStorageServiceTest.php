<?php

declare(strict_types=1);

use App\Models\UAELicenseHeldFor;
use App\Services\CQF\NonMotor\LOBs\BikeCQFQuoteStorageService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
    $this->service = app(BikeCQFQuoteStorageService::class);
});

it('increments uae_license_held_for_id to the next active entry', function () {
    $current = UAELicenseHeldFor::factory()->create(['text' => '1 year']);
    $next = UAELicenseHeldFor::factory()->create(['text' => '2 years']);

    $increment = Closure::bind(
        fn ($id) => $this->incrementLicenseHeldForId($id),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    expect($increment($current->id))->toBe($next->id);
});

it('keeps uae_license_held_for_id at cap when already at the highest active entry', function () {
    $cap = UAELicenseHeldFor::factory()->create(['text' => '5 years and above']);

    $increment = Closure::bind(
        fn ($id) => $this->incrementLicenseHeldForId($id),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    expect($increment($cap->id))->toBe($cap->id);
});

it('skips inactive entries when incrementing uae_license_held_for_id', function () {
    $current = UAELicenseHeldFor::factory()->create(['text' => '4 years']);
    UAELicenseHeldFor::factory()->inactive()->create(['text' => 'deleted entry']);
    $active = UAELicenseHeldFor::factory()->create(['text' => '5 years and above']);

    $increment = Closure::bind(
        fn ($id) => $this->incrementLicenseHeldForId($id),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    expect($increment($current->id))->toBe($active->id);
});

it('returns null when incrementing a null uae_license_held_for_id', function () {
    $increment = Closure::bind(
        fn ($id) => $this->incrementLicenseHeldForId($id),
        $this->service,
        BikeCQFQuoteStorageService::class
    );

    expect($increment(null))->toBeNull();
});
