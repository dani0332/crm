<?php

declare(strict_types=1);

use App\Console\Commands\ProcessNonMotorCQFRenewalLeads;
use App\Enums\ApplicationStorageEnums;
use App\Jobs\CQF\ProcessNonMotorCQFOrchestratorJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\TestDataSeeder;

it('registers command leads:process-non-motor-cqf-renewals', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('leads:process-non-motor-cqf-renewals')
        ->and($commands['leads:process-non-motor-cqf-renewals']->getName())->toBe('leads:process-non-motor-cqf-renewals');
});

it('does not dispatch job when non-motor CQF renewals switch is disabled', function () {
    Queue::fake();

    TestDataSeeder::seedApplicationStorage([
        ApplicationStorageEnums::NON_MOTOR_CQF_RENEWALS_SWITCH => 0,
    ]);

    $exitCode = app(ProcessNonMotorCQFRenewalLeads::class)->handle();

    expect($exitCode)->toBe(0);
    Queue::assertNothingPushed();
});

it('dispatches orchestrator job when non-motor CQF renewals switch is enabled', function () {
    Queue::fake();

    TestDataSeeder::seedApplicationStorage([
        ApplicationStorageEnums::NON_MOTOR_CQF_RENEWALS_SWITCH => 1,
    ]);

    $exitCode = app(ProcessNonMotorCQFRenewalLeads::class)->handle();

    expect($exitCode)->toBe(0);
    Queue::assertPushed(ProcessNonMotorCQFOrchestratorJob::class);
});
