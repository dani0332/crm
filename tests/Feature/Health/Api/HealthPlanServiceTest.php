<?php

use App\Enums\HealthBusinessTypeEnum;
use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use App\Models\HealthRate;
use App\Models\HealthRateControl;
use App\Models\User;
use App\Services\HealthPlanService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

it('creates a health plan with the given attributes', function () {
    $service = app(HealthPlanService::class);
    $code = 'HP-SVC-'.uniqid();

    $plan = $service->create([
        'code' => $code,
        'text' => 'Service create test',
        'health_business_type' => HealthBusinessTypeEnum::EBP->value,
        'is_active' => true,
        'is_hidden' => false,
        'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
    ]);

    expect($plan)->toBeInstanceOf(HealthPlan::class)
        ->and($plan->exists)->toBeTrue()
        ->and($plan->code)->toBe($code)
        ->and($plan->text)->toBe('Service create test');

    $this->assertDatabaseHas('health_plan', [
        'id' => $plan->id,
        'code' => $code,
        'health_business_type' => HealthBusinessTypeEnum::EBP->value,
        'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
    ]);
});

it('updates a draft health plan in place', function () {
    $service = app(HealthPlanService::class);
    $code = 'HP-UPD-'.uniqid();

    $plan = $service->create([
        'code' => $code,
        'text' => 'Original text',
        'health_business_type' => HealthBusinessTypeEnum::EBP->value,
        'is_active' => true,
        'is_hidden' => false,
        'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
    ]);

    $updated = $service->update($plan->id, [
        'text' => 'Updated text',
        'text_ar' => 'محدث',
        'health_business_type' => HealthBusinessTypeEnum::RM->value,
        'is_active' => false,
        'is_hidden' => true,
    ]);

    expect($updated->id)->toBe($plan->id)
        ->and($updated->text)->toBe('Updated text')
        ->and($updated->text_ar)->toBe('محدث')
        ->and($updated->health_business_type)->toBe(HealthBusinessTypeEnum::RM->value);

    $this->assertDatabaseHas('health_plan', [
        'id' => $plan->id,
        'code' => $code,
        'text' => 'Updated text',
        'text_ar' => 'محدث',
        'health_business_type' => HealthBusinessTypeEnum::RM->value,
        'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
    ]);

    expect(HealthPlan::where('code', $code)->count())->toBe(1);
});

it('publishes a draft health plan and schedules its draft rate sheet and rates', function () {
    $service = app(HealthPlanService::class);
    $code = 'HP-PUB-'.uniqid();

    $plan = $service->create([
        'code' => $code,
        'text' => 'Publish test plan',
        'health_business_type' => HealthBusinessTypeEnum::EBP->value,
        'is_active' => true,
        'is_hidden' => false,
        'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
    ]);

    $control = HealthRateControl::factory()->create([
        'health_plan_id' => $plan->id,
        'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
        'effective_from' => now()->addDay()->toDateString(),
    ]);

    HealthRate::factory()->create([
        'health_plan_id' => $plan->id,
        'health_rate_control_id' => $control->id,
        'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
    ]);

    $user = User::factory()->create();

    $service->publish($plan->id, $user->id);

    $this->assertDatabaseHas('health_plan', [
        'id' => $plan->id,
        'status' => HealthPlanRateSheetStatusEnum::SCHEDULED->value,
    ]);

    $this->assertDatabaseHas('health_rates_control', [
        'id' => $control->id,
        'status' => HealthPlanRateSheetStatusEnum::SCHEDULED->value,
        'published_by' => $user->id,
        'published_at' => now()->toDateString(),
    ]);

    $this->assertDatabaseHas('health_rates', [
        'health_rate_control_id' => $control->id,
        'status' => HealthPlanRateSheetStatusEnum::SCHEDULED->value,
    ]);
});
