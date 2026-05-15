<?php

use App\Enums\HealthBusinessTypeEnum;
use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use App\Services\HealthPlanService;
use Illuminate\Support\Facades\DB;
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

    expect(DB::table('health_plan')->where([
        'id' => $plan->id,
        'code' => $code,
        'health_business_type' => HealthBusinessTypeEnum::EBP->value,
        'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
    ])->exists())->toBeTrue();
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

    expect(DB::table('health_plan')->where([
        'id' => $plan->id,
        'code' => $code,
        'text' => 'Updated text',
        'text_ar' => 'محدث',
        'health_business_type' => HealthBusinessTypeEnum::RM->value,
        'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
    ])->exists())->toBeTrue();

    expect(DB::table('health_plan')->where('code', $code)->count())->toBe(1);
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

    $controlId = DB::table('health_rates_control')->insertGetId([
        'health_plan_id' => $plan->id,
        'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
        'effective_from' => now()->addDay()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('health_rates')->insert([
        'health_plan_id' => $plan->id,
        'health_rate_control_id' => $controlId,
        'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $userId = DB::table('users')->insertGetId([
        'name' => 'Publisher',
        'email' => uniqid('pub_').'@example.com',
        'password' => 'password',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service->publish($plan->id, $userId);

    expect(DB::table('health_plan')->where([
        'id' => $plan->id,
        'status' => HealthPlanRateSheetStatusEnum::SCHEDULED->value,
    ])->exists())->toBeTrue();

    $control = DB::table('health_rates_control')->where('id', $controlId)->first();

    expect($control->status)->toBe(HealthPlanRateSheetStatusEnum::SCHEDULED->value)
        ->and($control->published_by)->toBe($userId)
        ->and($control->published_at)->toBe(now()->toDateString());

    expect(DB::table('health_rates')->where([
        'health_rate_control_id' => $controlId,
        'status' => HealthPlanRateSheetStatusEnum::SCHEDULED->value,
    ])->exists())->toBeTrue();
});
