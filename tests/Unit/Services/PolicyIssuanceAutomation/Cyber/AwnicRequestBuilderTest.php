<?php

namespace Tests\Unit\Services\PolicyIssuanceAutomation\Cyber;

use App\Services\PolicyIssuanceAutomation\Cyber\AwnicRequestBuilder;
use Tests\TestCase;

class AwnicRequestBuilderTest extends TestCase
{
    public function test_builds_issue_policy_payload_with_required_fields(): void
    {
        config()->set('constants.AWNIC_API_BROKER_NO', '999');

        $builder = new AwnicRequestBuilder();

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

        $this->assertSame('John Doe', $payload['CustName']);
        $this->assertSame('0500000000', $payload['CustMobile']);
        $this->assertSame('john@example.com', $payload['CustEmail']);
        $this->assertSame('UAE', $payload['CustCountryCode']);
        $this->assertSame('Gold', $payload['PlanName']);
        $this->assertSame('999', $payload['CustCode']);
        $this->assertSame('999', $payload['BrokerCode']);
        $this->assertSame('REF123', $payload['PaymentRefNo']);
        $this->assertSame('Q-1', $payload['PartnerRefNo']);
    }
}
