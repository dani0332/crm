<?php

declare(strict_types=1);

use App\Enums\HealthPlanTypeEnum;
use App\Enums\HealthRoutingLogTypeEnum;
use App\Enums\HealthRoutingSourceEnum;
use App\Enums\HealthTeamType;
use App\Models\HealthQuote;
use App\Services\CanonicalNationalityService;
use App\Services\HealthTeamRouting\HealthTeamRoutingLogService;
use App\Services\HealthTeamRouting\HealthTeamRoutingService;
use App\Services\NationalityPoolService;

it('logs sic1 and returns without saving when lead is sic1', function () {
    $log = Mockery::mock(HealthTeamRoutingLogService::class);
    $log->shouldReceive('log')
        ->once()
        ->with(
            HealthRoutingLogTypeEnum::ROUTING,
            Mockery::on(fn (array $data) => ($data['step'] ?? '') === 'SIC1 check'),
            1,
            'auh-test-uuid',
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
                'id' => 1,
                'uuid' => 'auh-test-uuid',
            ], true);
        }

        public function isSIC1(): bool
        {
            return true;
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

    $service->triggerAUHTierRouting($lead);

    expect($lead->saveCount)->toBe(0);
});

it('logs sic2 then no auh team when premium is missing', function () {
    $log = Mockery::mock(HealthTeamRoutingLogService::class);
    $log->shouldReceive('log')
        ->once()
        ->with(
            HealthRoutingLogTypeEnum::ROUTING,
            Mockery::on(fn (array $data) => ($data['step'] ?? '') === 'SIC2 check'),
            2,
            'auh-sic2-uuid',
            null,
            HealthRoutingSourceEnum::ROUTING,
        );
    $log->shouldReceive('log')
        ->once()
        ->with(
            HealthRoutingLogTypeEnum::ROUTING,
            Mockery::on(fn (array $data) => ($data['message'] ?? '') === 'No AUH team found for given premium'),
            2,
            'auh-sic2-uuid',
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
                'id' => 2,
                'uuid' => 'auh-sic2-uuid',
                'price_starting_from' => null,
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

    $service->triggerAUHTierRouting($lead);

    expect($lead->saveCount)->toBe(0);
});

it('logs pec and entry good plan check then stops when no auh team for pec good lead', function () {
    $log = Mockery::mock(HealthTeamRoutingLogService::class);
    $log->shouldReceive('log')
        ->once()
        ->with(
            HealthRoutingLogTypeEnum::ROUTING,
            Mockery::on(fn (array $data) => ($data['step'] ?? '') === 'PEC lead check'),
            3,
            'auh-pec-good-uuid',
            null,
            HealthRoutingSourceEnum::ROUTING,
        );
    $log->shouldReceive('log')
        ->once()
        ->with(
            HealthRoutingLogTypeEnum::ROUTING,
            Mockery::on(fn (array $data) => ($data['step'] ?? '') === 'Health Plan type check'),
            3,
            'auh-pec-good-uuid',
            null,
            HealthRoutingSourceEnum::ROUTING,
        );
    $log->shouldReceive('log')
        ->once()
        ->with(
            HealthRoutingLogTypeEnum::ROUTING,
            Mockery::on(fn (array $data) => ($data['message'] ?? '') === 'No AUH team found for given premium'),
            3,
            'auh-pec-good-uuid',
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
                'id' => 3,
                'uuid' => 'auh-pec-good-uuid',
                'health_plan_type_id' => HealthPlanTypeEnum::GOOD->value,
                'price_starting_from' => null,
                'pec_marked_at' => now(),
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

    $service->triggerAUHTierRouting($lead);

    expect($lead->saveCount)->toBe(0);
});

it('assigns gbp team when pec best lead is gbp qualified', function () {
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
                'id' => 4,
                'uuid' => 'auh-pec-best-uuid',
                'health_plan_type_id' => HealthPlanTypeEnum::BEST->value,
                'price_starting_from' => 50_000,
                'nationality_id' => 1,
                'pec_marked_at' => now(),
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

    $service->triggerAUHTierRouting($lead);

    expect($lead->saveCount)->toBe(1)
        ->and($lead->health_team_type)->toBe((string) HealthTeamType::GBP);
});
