<?php

use Tests\Helpers\AmtGroupMedicalMockHelper;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->lookups = TestDataSeeder::seedAmtGroupMedicalLookups();
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
});

test('group medical lead created via AMT controller is sent to CAPI with emirate of registration id', function () {
    $emirateId = $this->lookups['emirate_of_registration_id'];
    $businessTypeId = $this->lookups['business_type_of_insurance_id'];
    $natureOfCompanyActivityId = $this->lookups['nature_of_company_activity_id'];
    $healthPlanTypeId = $this->lookups['health_plan_type_id'];

    AmtGroupMedicalMockHelper::mockCapiRequestService($emirateId);

    $payload = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@gmail.com',
        'mobile_no' => '0501234567',
        'business_type_of_insurance_id' => $businessTypeId,
        'company_name' => 'Test Company LLC',
        'brief_details' => 'Group medical coverage required.',
        'emirate_of_registration_id' => $emirateId,
        'nature_of_company_activity_id' => $natureOfCompanyActivityId,
        'has_existing_group_health_insurance' => false,
        'health_plan_type_id' => $healthPlanTypeId,
        'number_of_categories' => 1,
        'categories' => [
            ['numberOfPeople' => 10],
        ],
    ];

    $response = $this->post(route('amt.store'), $payload);

    $response->assertRedirect(route('amt.index'));
    $response->assertSessionHas('success', 'Lead has been stored');
});
