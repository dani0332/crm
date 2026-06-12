<?php

declare(strict_types=1);

use App\Enums\HealthPlanTypeEnum;
use App\Enums\HealthRoutingSourceEnum;
use App\Models\HealthQuote;
use App\Services\CanonicalNationalityService;
use App\Services\HealthTeamRouting\HealthTeamRoutingLogService;
use App\Services\HealthTeamRouting\HealthTeamRoutingService;
use App\Services\NationalityPoolService;

it('returns false when health plan type is not BEST', function () {
    $canonicalNationalityService = Mockery::mock(CanonicalNationalityService::class);
    $canonicalNationalityService->shouldReceive('getByNationalityId')->andReturnNull();

    $nationalityPoolService = Mockery::mock(NationalityPoolService::class);
    $nationalityPoolService->shouldReceive('getNationalityCodes')->andReturnNull();

    $service = new HealthTeamRoutingService(
        Mockery::mock(HealthTeamRoutingLogService::class),
        $canonicalNationalityService,
        $nationalityPoolService,
        HealthRoutingSourceEnum::ROUTING,
    );

    $lead = new HealthQuote;
    $lead->health_plan_type_id = HealthPlanTypeEnum::GOOD->value;
    $lead->price_starting_from = 50_000;
    $lead->nationality_id = 1;

    expect($service->isGBPQualified($lead))->toBeFalse();
});
