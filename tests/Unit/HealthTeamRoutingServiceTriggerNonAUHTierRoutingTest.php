<?php

declare(strict_types=1);

use App\Enums\HealthPlanTypeEnum;
use App\Enums\HealthRoutingLogTypeEnum;
use App\Enums\HealthRoutingSourceEnum;
use App\Enums\HealthTeamType;
use App\Enums\TeamNameEnum;
use App\Models\HealthQuote;
use App\Services\CanonicalNationalityService;
use App\Services\HealthTeamRouting\HealthTeamRoutingLogService;
use App\Services\HealthTeamRouting\HealthTeamRoutingService;
use App\Services\NationalityPoolService;

it('logs sic1 and returns without saving when lead is sic1 for non auh routing', function () {
    $log = Mockery::mock(HealthTeamRoutingLogService::class);
    $log->shouldReceive('log')
        ->once()
        ->with(
            HealthRoutingLogTypeEnum::ROUTING,
            Mockery::on(fn (array $data) => ($data['step'] ?? '') === 'SIC1 check'),
            101,
            'non-auh-sic1-uuid',
            null,
            HealthRoutingSourceEnum::ROUTING,
        );

    $service = new HealthTeamRoutingService(
        $log,
        Mockery::mock(CanonicalNationalityService::class),
        Mockery::mock(NationalityPoolService::class),
        HealthRoutingSourceEnum::ROUTING,
    );

    $lead = new class extends HealthQuote
    {
        public int $saveCount = 0;

        public function __construct()
        {
            parent::__construct();
            $this->setRawAttributes([
                'id' => 101,
                'uuid' => 'non-auh-sic1-uuid',
            ], true);
        }

        public function isSIC1(): bool
        {
            return true;
        }

        public function save(array $options = []): bool
        {
            $this->saveCount++;

            return true;
        }
    };

    $service->triggerNonAUHTierRouting($lead);

    expect($lead->saveCount)->toBe(0);
});

it('stores notional gbp for sic2 non pec lead when gbp qualified for notion', function () {
    $log = Mockery::mock(HealthTeamRoutingLogService::class);
    $log->shouldReceive('log')->andReturnNull();

    $service = new class($log, Mockery::mock(CanonicalNationalityService::class), Mockery::mock(NationalityPoolService::class), HealthRoutingSourceEnum::ROUTING) extends HealthTeamRoutingService
    {
        public function isGBPQualified(HealthQuote $lead): bool
        {
            return true;
        }
    };

    $lead = new class extends HealthQuote
    {
        public int $saveCount = 0;

        public function __construct()
        {
            parent::__construct();
            $this->setRawAttributes([
                'id' => 102,
                'uuid' => 'non-auh-notional-gbp',
                'health_plan_type_id' => HealthPlanTypeEnum::BEST->value,
            ], true);
        }

        public function isSIC1(): bool
        {
            return false;
        }

        public function isSIC2(): bool
        {
            return true;
        }

        public function isPECLead(): bool
        {
            return false;
        }

        public function hasAnyMemberAgeSixtyOrAbove(): bool
        {
            return false;
        }

        public function save(array $options = []): bool
        {
            $this->saveCount++;

            return true;
        }
    };

    $service->triggerNonAUHTierRouting($lead);

    expect($lead->saveCount)->toBe(1)
        ->and($lead->notional_team)->toBe((string) HealthTeamType::GBP)
        ->and($lead->health_team_type)->toBeNull();
});

it('stores notional team from plan type for sic2 non pec lead when not gbp qualified', function () {
    $log = Mockery::mock(HealthTeamRoutingLogService::class);
    $log->shouldReceive('log')->andReturnNull();

    $service = new class($log, Mockery::mock(CanonicalNationalityService::class), Mockery::mock(NationalityPoolService::class), HealthRoutingSourceEnum::ROUTING) extends HealthTeamRoutingService
    {
        public function isGBPQualified(HealthQuote $lead): bool
        {
            return false;
        }
    };

    $lead = new class extends HealthQuote
    {
        public int $saveCount = 0;

        public function __construct()
        {
            parent::__construct();
            $this->setRawAttributes([
                'id' => 103,
                'uuid' => 'non-auh-notional-good',
                'health_plan_type_id' => HealthPlanTypeEnum::GOOD->value,
            ], true);
        }

        public function isSIC1(): bool
        {
            return false;
        }

        public function isSIC2(): bool
        {
            return true;
        }

        public function isPECLead(): bool
        {
            return false;
        }

        public function hasAnyMemberAgeSixtyOrAbove(): bool
        {
            return false;
        }

        public function save(array $options = []): bool
        {
            $this->saveCount++;

            return true;
        }
    };

    $service->triggerNonAUHTierRouting($lead);

    expect($lead->saveCount)->toBe(1)
        ->and($lead->notional_team)->toBe(TeamNameEnum::RM_SPEED)
        ->and($lead->health_team_type)->toBeNull();
});

it('assigns pec team for sic2 pec lead with entry level plan', function () {
    $log = Mockery::mock(HealthTeamRoutingLogService::class);
    $log->shouldReceive('log')->andReturnNull();

    $service = new class($log, Mockery::mock(CanonicalNationalityService::class), Mockery::mock(NationalityPoolService::class), HealthRoutingSourceEnum::ROUTING) extends HealthTeamRoutingService
    {
        public function isGBPQualified(HealthQuote $lead): bool
        {
            return false;
        }
    };

    $lead = new class extends HealthQuote
    {
        public int $saveCount = 0;

        public function __construct()
        {
            parent::__construct();
            $this->setRawAttributes([
                'id' => 104,
                'uuid' => 'non-auh-pec-entry',
                'health_plan_type_id' => HealthPlanTypeEnum::ENTRY_LEVEL->value,
                'pec_marked_at' => now(),
            ], true);
        }

        public function isSIC1(): bool
        {
            return false;
        }

        public function isSIC2(): bool
        {
            return true;
        }

        public function isPECLead(): bool
        {
            return true;
        }

        public function hasAnyMemberAgeSixtyOrAbove(): bool
        {
            return false;
        }

        public function save(array $options = []): bool
        {
            $this->saveCount++;

            return true;
        }
    };

    $service->triggerNonAUHTierRouting($lead);

    expect($lead->saveCount)->toBe(1)
        ->and($lead->health_team_type)->toBe(TeamNameEnum::PEC);
});

it('assigns gbp for sic2 pec best lead when gbp qualified', function () {
    $log = Mockery::mock(HealthTeamRoutingLogService::class);
    $log->shouldReceive('log')->andReturnNull();

    $service = new class($log, Mockery::mock(CanonicalNationalityService::class), Mockery::mock(NationalityPoolService::class), HealthRoutingSourceEnum::ROUTING) extends HealthTeamRoutingService
    {
        public function isGBPQualified(HealthQuote $lead): bool
        {
            return true;
        }
    };

    $lead = new class extends HealthQuote
    {
        public int $saveCount = 0;

        public function __construct()
        {
            parent::__construct();
            $this->setRawAttributes([
                'id' => 105,
                'uuid' => 'non-auh-pec-best-gbp',
                'health_plan_type_id' => HealthPlanTypeEnum::BEST->value,
                'nationality_id' => 1,
                'price_starting_from' => 100_000,
                'pec_marked_at' => now(),
            ], true);
        }

        public function isSIC1(): bool
        {
            return false;
        }

        public function isSIC2(): bool
        {
            return true;
        }

        public function isPECLead(): bool
        {
            return true;
        }

        public function hasAnyMemberAgeSixtyOrAbove(): bool
        {
            return false;
        }

        public function save(array $options = []): bool
        {
            $this->saveCount++;

            return true;
        }
    };

    $service->triggerNonAUHTierRouting($lead);

    expect($lead->saveCount)->toBe(1)
        ->and($lead->health_team_type)->toBe((string) HealthTeamType::GBP);
});

it('assigns best team from plan type when sic2 pec best lead is not gbp qualified', function () {
    $log = Mockery::mock(HealthTeamRoutingLogService::class);
    $log->shouldReceive('log')->andReturnNull();

    $service = new class($log, Mockery::mock(CanonicalNationalityService::class), Mockery::mock(NationalityPoolService::class), HealthRoutingSourceEnum::ROUTING) extends HealthTeamRoutingService
    {
        public function isGBPQualified(HealthQuote $lead): bool
        {
            return false;
        }
    };

    $lead = new class extends HealthQuote
    {
        public int $saveCount = 0;

        public function __construct()
        {
            parent::__construct();
            $this->setRawAttributes([
                'id' => 106,
                'uuid' => 'non-auh-pec-best-fallback',
                'health_plan_type_id' => HealthPlanTypeEnum::BEST->value,
                'nationality_id' => 1,
                'price_starting_from' => 1,
                'pec_marked_at' => now(),
            ], true);
        }

        public function isSIC1(): bool
        {
            return false;
        }

        public function isSIC2(): bool
        {
            return true;
        }

        public function isPECLead(): bool
        {
            return true;
        }

        public function hasAnyMemberAgeSixtyOrAbove(): bool
        {
            return false;
        }

        public function save(array $options = []): bool
        {
            $this->saveCount++;

            return true;
        }
    };

    $service->triggerNonAUHTierRouting($lead);

    expect($lead->saveCount)->toBe(1)
        ->and($lead->health_team_type)->toBe(TeamNameEnum::RM_NB);
});

it('assigns team from plan type when lead is not sic2', function () {
    $log = Mockery::mock(HealthTeamRoutingLogService::class);
    $log->shouldReceive('log')->andReturnNull();

    $canonicalNationalityService = Mockery::mock(CanonicalNationalityService::class);
    $canonicalNationalityService->shouldReceive('getByNationalityId')->andReturnNull();

    $nationalityPoolService = Mockery::mock(NationalityPoolService::class);
    $nationalityPoolService->shouldReceive('getNationalityCodes')->andReturnNull();

    $service = new HealthTeamRoutingService(
        $log,
        $canonicalNationalityService,
        $nationalityPoolService,
        HealthRoutingSourceEnum::ROUTING,
    );

    $lead = new class extends HealthQuote
    {
        public int $saveCount = 0;

        public function __construct()
        {
            parent::__construct();
            $this->setRawAttributes([
                'id' => 107,
                'uuid' => 'non-auh-direct-good',
                'health_plan_type_id' => HealthPlanTypeEnum::GOOD->value,
                'nationality_id' => 1,
            ], true);
        }

        public function isSIC1(): bool
        {
            return false;
        }

        public function isSIC2(): bool
        {
            return false;
        }

        public function save(array $options = []): bool
        {
            $this->saveCount++;

            return true;
        }
    };

    $service->triggerNonAUHTierRouting($lead);

    expect($lead->saveCount)->toBe(1)
        ->and($lead->health_team_type)->toBeNull();
});
