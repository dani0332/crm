<?php

declare(strict_types=1);

use App\Enums\InsuranceProvidersEnum;
use App\Models\CarPlan;
use App\Models\InsuranceProvider;
use App\Services\RenewalsUploadService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
});

afterEach(function () {
    Mockery::close();
});

if (! function_exists('createRenewalsUploadServiceWithMocks')) {
    /**
     * Create RenewalsUploadService instance using reflection to bypass constructor.
     */
    function createRenewalsUploadServiceWithMocks(): RenewalsUploadService
    {
        static $reflection = null;

        if ($reflection === null) {
            $reflection = new \ReflectionClass(RenewalsUploadService::class);
        }

        return $reflection->newInstanceWithoutConstructor();
    }
}

test('returns genesis lead details when insurer is RSA and plan exists', function () {
    $provider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::AXA,
        'text' => 'AXA',
    ]);

    $plan = CarPlan::create([
        'text' => 'Smart Plan',
        'repair_type' => 'TPL',
        'provider_id' => $provider->id,
    ]);

    $service = createRenewalsUploadServiceWithMocks();
    $leadValidationErrors = collect();
    $leadData = (object) [
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $provider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ];

    $result = $service->isGenesisLead($leadData, $leadValidationErrors);

    expect($result['status'])->toBeTrue()
        ->and($result['carPlan']->is($plan))->toBeTrue()
        ->and($result['insuranceProvider']->is($provider))->toBeTrue()
        ->and($leadValidationErrors)->toBeEmpty();
});

test('adds validation error when genesis lead plan is missing', function () {
    $provider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::AXA,
        'text' => 'AXA',
    ]);

    $service = createRenewalsUploadServiceWithMocks();
    $leadValidationErrors = collect();
    $leadData = (object) [
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $provider->text,
        'plan_name' => 'Unknown Plan',
        'plan_type' => 'TPL',
    ];

    $result = $service->isGenesisLead($leadData, $leadValidationErrors);

    expect($result['status'])->toBeFalse()
        ->and($result['carPlan'])->toBeNull()
        ->and($result['insuranceProvider']->is($provider))->toBeTrue()
        ->and($leadValidationErrors)->toContain('Invalid Insurer Plan Name or Repair Type for Genesis Lead');
});

test('returns non-genesis details for non RSA insurer while plan exists', function () {
    $provider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::AXA,
        'text' => 'AXA',
    ]);

    $plan = CarPlan::create([
        'text' => 'Comprehensive',
        'repair_type' => 'COMP',
        'provider_id' => $provider->id,
    ]);

    $service = createRenewalsUploadServiceWithMocks();
    $leadValidationErrors = collect();
    $leadData = (object) [
        'insurer' => InsuranceProvidersEnum::AXA,
        'provider_name' => $provider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ];

    $result = $service->isGenesisLead($leadData, $leadValidationErrors);

    expect($result['status'])->toBeFalse()
        ->and($result['carPlan']->is($plan))->toBeTrue()
        ->and($result['insuranceProvider']->is($provider))->toBeTrue()
        ->and($leadValidationErrors)->toBeEmpty();
});

test('isGenesisLead completes within 100 milliseconds', function () {
    $provider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::AXA,
        'text' => 'AXA',
    ]);

    CarPlan::create([
        'text' => 'Quick Plan',
        'repair_type' => 'TPL',
        'provider_id' => $provider->id,
    ]);

    $service = createRenewalsUploadServiceWithMocks();
    $leadValidationErrors = collect();
    $leadData = (object) [
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $provider->text,
        'plan_name' => 'Quick Plan',
        'plan_type' => 'TPL',
    ];

    $start = microtime(true);
    $service->isGenesisLead($leadData, $leadValidationErrors);
    $duration = microtime(true) - $start;

    expect($duration)->toBeLessThan(0.1);
});
