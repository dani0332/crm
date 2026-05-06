<?php

declare(strict_types=1);

use App\Enums\HealthPlanTypeEnum;
use App\Enums\HealthRoutingSourceEnum;
use App\Enums\TeamNameEnum;
use App\Models\HealthQuote;
use App\Services\CanonicalNationalityService;
use App\Services\HealthTeamRouting\HealthTeamRoutingLogService;
use App\Services\HealthTeamRouting\HealthTeamRoutingService;
use App\Services\NationalityPoolService;

it('assigns non auh pec team when lead is a pec lead', function () {
    $log = Mockery::mock(HealthTeamRoutingLogService::class);
    $log->shouldReceive('log')->andReturnNull();

    $service = new class($log, Mockery::mock(CanonicalNationalityService::class), Mockery::mock(NationalityPoolService::class), HealthRoutingSourceEnum::ROUTING) extends HealthTeamRoutingService
    {
        public function isGBPQualified(HealthQuote $lead): bool
        {
            return false;
        }
    };

    $quote = new class extends HealthQuote
    {
        public function __construct()
        {
            parent::__construct();
            $this->setRawAttributes([
                'id' => 1,
                'uuid' => 'lead-uuid-pec',
                'pec_marked_at' => null,
                'health_plan_type_id' => HealthPlanTypeEnum::GOOD->value,
            ], true);
            $this->syncOriginal();
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
            return true;
        }
    };

    $service->triggerNonAUHTierRouting($quote);

    expect($quote->health_team_type)->toBe(TeamNameEnum::PEC);
});

it('assigns non auh pec team when any member is age sixty or above', function () {
    $log = Mockery::mock(HealthTeamRoutingLogService::class);
    $log->shouldReceive('log')->andReturnNull();

    $service = new class($log, Mockery::mock(CanonicalNationalityService::class), Mockery::mock(NationalityPoolService::class), HealthRoutingSourceEnum::ROUTING) extends HealthTeamRoutingService
    {
        public function isGBPQualified(HealthQuote $lead): bool
        {
            return false;
        }
    };

    $quote = new class extends HealthQuote
    {
        public function __construct()
        {
            parent::__construct();
            $this->setRawAttributes([
                'id' => 2,
                'uuid' => 'lead-uuid-age',
                'pec_marked_at' => null,
                'health_plan_type_id' => HealthPlanTypeEnum::GOOD->value,
            ], true);
            $this->syncOriginal();
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
            return true;
        }

        public function save(array $options = []): bool
        {
            return true;
        }
    };

    $service->triggerNonAUHTierRouting($quote);

    expect($quote->health_team_type)->toBe(TeamNameEnum::PEC);
});
