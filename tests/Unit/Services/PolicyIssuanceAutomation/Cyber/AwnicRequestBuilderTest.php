<?php

use App\Services\PolicyIssuanceAutomation\Cyber\AwnicRequestBuilder;

it('builds issue policy payload with required fields', function () {
    config()->set('constants.AWNIC_API_BROKER_NO', '999');

    $builder = new AwnicRequestBuilder;

    $quote = (object) [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_no' => '0500000000',
        'email' => 'john@example.com',
        'code' => 'Q-1',
        'latestInsured' => ['id_type' => 'emiratesId', 'id_number' => '784-123-1234567-1'],
    ];

    $customer = (object) ['dob' => '1990-01-01'];
    $nationality = (object) ['awni_country_code' => 'UAE'];
    $planDetail = (object) ['coverage' => 500000, 'planName' => 'Gold'];
    $splitPayment = (object) ['reference' => 'REF123'];
    $emirate = (object) ['text' => 'Dubai'];

    $payload = $builder->buildIssuePolicyPayload(
        $quote,
        $customer,
        $nationality,
        $planDetail,
        $splitPayment,
        $emirate
    );

    expect($payload)->toMatchArray([
        'CustName' => 'John Doe',
        'CustMobile' => '0500000000',
        'CustEmail' => 'john@example.com',
        'CustCountryCode' => 'UAE',
        'PlanName' => 'Gold',
        'CustCode' => '999',
        'BrokerCode' => '999',
        'PaymentRefNo' => 'REF123',
        'PartnerRefNo' => 'Q-1',
    ]);
});
