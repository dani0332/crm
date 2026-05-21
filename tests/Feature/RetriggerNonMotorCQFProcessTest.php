<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Http\Middleware\PreventRequestForgery;
use App\Jobs\CQF\ProcessNonMotorCQFOrchestratorJob;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->withoutMiddleware(PreventRequestForgery::class);
    $this->user = TestDataSeeder::createAdminUser();
});

it('redirects with error when the non-motor CQF renewals switch is disabled', function () {
    TestDataSeeder::seedApplicationStorage([
        ApplicationStorageEnums::NON_MOTOR_CQF_RENEWALS_SWITCH => 0,
    ]);

    $this->actingAs($this->user)
        ->post(route('renewals-non-motor-retrigger'))
        ->assertRedirect(route('renewals-upload-create'))
        ->assertSessionHas('error');
});

it('dispatches orchestrator job and redirects with success when switch is enabled', function () {
    Queue::fake();

    TestDataSeeder::seedApplicationStorage([
        ApplicationStorageEnums::NON_MOTOR_CQF_RENEWALS_SWITCH => 1,
    ]);

    $this->actingAs($this->user)
        ->post(route('renewals-non-motor-retrigger'))
        ->assertRedirect(route('renewals-upload-create'))
        ->assertSessionHas('success');

    Queue::assertPushed(ProcessNonMotorCQFOrchestratorJob::class);
});
