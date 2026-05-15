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

    AmtGroupMedicalMockHelper::mockCapiRequestService($emirateId);

    $payload = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@gmail.com',
        'mobile_no' => '0501234567',
        'business_type_of_insurance_id' => $businessTypeId,
        'company_name' => 'Test Company LLC',
        'number_of_employees' => 10,
        'brief_details' => 'Group medical coverage required.',
        'emirate_of_registration_id' => $emirateId,
    ];

    $response = $this->post(route('amt.store'), $payload);

    $response->assertRedirect(route('amt.index'));
    $response->assertSessionHas('success', 'Lead has been stored');
});
