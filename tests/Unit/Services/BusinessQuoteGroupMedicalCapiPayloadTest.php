<?php

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Services\BusinessQuoteService;
use Illuminate\Http\Request;
use ReflectionMethod;

/**
 * @return array<string, mixed>
 */
function invokeGroupMedicalCapiPayload(Request $request): array
{
    $service = app(BusinessQuoteService::class);
    $method = new ReflectionMethod(BusinessQuoteService::class, 'buildGroupMedicalCapiPayload');
    $method->setAccessible(true);

    return $method->invoke($service, $request);
}

test('buildGroupMedicalCapiPayload returns empty array for non group medical business type', function () {
    $request = Request::create('/', 'POST', [
        'business_type_of_insurance_id' => BusinessTypeOfInsuranceIdEnum::PROPERTY,
        'health_plan_type_id' => 13,
    ]);

    expect(invokeGroupMedicalCapiPayload($request))->toBe([]);
});

test('buildGroupMedicalCapiPayload maps AMT snake_case intake fields to CAPI camelCase', function () {
    $request = Request::create('/', 'POST', [
        'business_type_of_insurance_id' => BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL,
        'nature_of_company_activity_id' => 3,
        'has_existing_group_health_insurance' => true,
        'health_plan_type_id' => 13,
        'number_of_categories' => 2,
        'gm_category_intake' => [
            [
                'member_category_id' => 1,
                'number_of_people' => 40,
                'existing_insurance_provider_id' => 4,
                'existing_tpa_id' => 8,
                'existing_network_id' => 7,
                'existing_policy_renewal_date' => '2026-07-01',
            ],
            [
                'member_category_id' => 3,
                'number_of_people' => 30,
                'existing_insurance_provider_id' => 2,
                'existing_tpa_id' => 11,
                'existing_network_id' => 8,
                'existing_policy_renewal_date' => '2026-07-01',
            ],
        ],
    ]);

    expect(invokeGroupMedicalCapiPayload($request))->toBe([
        'companyActivityTypeId' => 3,
        'hasExistingGroupPolicy' => true,
        'healthPlanTypeId' => 13,
        'categories' => [
            [
                'groupMedicalCategoryId' => 1,
                'numberOfPeople' => 40,
                'insuranceProviderId' => 4,
                'healthTpaId' => 8,
                'healthNetworkId' => 7,
                'renewalDate' => '2026-07-01',
            ],
            [
                'groupMedicalCategoryId' => 3,
                'numberOfPeople' => 30,
                'insuranceProviderId' => 2,
                'healthTpaId' => 11,
                'healthNetworkId' => 8,
                'renewalDate' => '2026-07-01',
            ],
        ],
    ]);
});

test('buildGroupMedicalCapiPayload maps ecommerce camelCase payload', function () {
    $request = Request::create('/', 'POST', [
        'businessTypeOfInsuranceId' => BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL,
        'quoteUID' => 'DPL7PA7L',
        'companyActivityTypeId' => 3,
        'hasExistingGroupPolicy' => true,
        'healthPlanTypeId' => 13,
        'numberOfCategories' => 2,
        'categories' => [
            [
                'groupMedicalCategoryId' => 1,
                'numberOfPeople' => 40,
                'insuranceProviderId' => 4,
                'healthTpaId' => 8,
                'healthNetworkId' => 7,
                'renewalDate' => '2026-07-01',
            ],
        ],
    ]);

    $payload = invokeGroupMedicalCapiPayload($request);

    expect($payload)->toHaveKeys([
        'companyActivityTypeId',
        'hasExistingGroupPolicy',
        'healthPlanTypeId',
        'categories',
    ])
        ->and($payload)->not->toHaveKey('quoteUID')
        ->and($payload)->not->toHaveKey('numberOfCategories')
        ->and($payload['categories'][0])->toMatchArray([
            'groupMedicalCategoryId' => 1,
            'numberOfPeople' => 40,
            'insuranceProviderId' => 4,
            'healthTpaId' => 8,
            'healthNetworkId' => 7,
            'renewalDate' => '2026-07-01',
        ]);
});
