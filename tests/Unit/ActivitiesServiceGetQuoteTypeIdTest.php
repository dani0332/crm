<?php

declare(strict_types=1);

use App\Enums\quoteTypeCode;

it('normalizes only the canonical HomeAppliance segment', function () {
    expect(quoteTypeCode::normalizeRouteSegment('HomeAppliance'))->toBe(quoteTypeCode::HOME_APPLIANCE)
        ->and(quoteTypeCode::normalizeRouteSegment('home_appliance'))->toBeNull()
        ->and(quoteTypeCode::normalizeRouteSegment('car'))->toBeNull();
});
