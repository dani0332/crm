<?php

declare(strict_types=1);

namespace Tests\Unit\Services\EmailServices;

use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Services\EmailServices\FailedILAEmailService;
use Mockery;
use Tests\TestCase;

class FailedILAEmailServiceTest extends TestCase
{
    private FailedILAEmailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FailedILAEmailService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_build_failed_ila_email_travel_data()
    {
        $quoteType = QuoteTypes::TRAVEL;
        $managerEmails = ['manager1@test.com', 'manager2@test.com'];

        $result = $this->service->buildFailedIlaEmailData($quoteType, $managerEmails);

        $this->assertIsArray($result);
        $this->assertEquals($quoteType, $result['quoteType']);
        $this->assertEquals(WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS, $result['workflowType']);
        $this->assertEquals(now()->format('Y-m-d'), $result['dateOfAttempt']);
        $this->assertIsString($result['fileDownloadUrl']);
        $this->assertNotEmpty($result['fileDownloadUrl']);
    }

    public function test_build_failed_ila_email_car_data()
    {
        $quoteType = QuoteTypes::CAR;
        $managerEmails = ['manager1@test.com', 'manager2@test.com'];

        $result = $this->service->buildFailedIlaEmailData($quoteType, $managerEmails);

        $this->assertIsArray($result);
        $this->assertEquals($quoteType, $result['quoteType']);
        $this->assertEquals(WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS, $result['workflowType']);
        $this->assertEquals(now()->format('Y-m-d'), $result['dateOfAttempt']);
        $this->assertIsString($result['fileDownloadUrl']);
        $this->assertNotEmpty($result['fileDownloadUrl']);
    }

    public function test_build_failed_ila_email_bike_data()
    {
        $quoteType = QuoteTypes::BIKE;
        $managerEmails = ['manager1@test.com', 'manager2@test.com'];

        $result = $this->service->buildFailedIlaEmailData($quoteType, $managerEmails);

        $this->assertIsArray($result);
        $this->assertEquals($quoteType, $result['quoteType']);
        $this->assertEquals(WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS, $result['workflowType']);
        $this->assertEquals(now()->format('Y-m-d'), $result['dateOfAttempt']);
        $this->assertIsString($result['fileDownloadUrl']);
        $this->assertNotEmpty($result['fileDownloadUrl']);
    }

    public function test_build_failed_ila_email_health_data()
    {
        $quoteType = QuoteTypes::HEALTH;
        $managerEmails = ['manager1@test.com', 'manager2@test.com'];

        $result = $this->service->buildFailedIlaEmailData($quoteType, $managerEmails);

        $this->assertIsArray($result);
        $this->assertEquals($quoteType, $result['quoteType']);
        $this->assertEquals(WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS, $result['workflowType']);
        $this->assertEquals(now()->format('Y-m-d'), $result['dateOfAttempt']);
        $this->assertIsString($result['fileDownloadUrl']);
        $this->assertNotEmpty($result['fileDownloadUrl']);
    }

    public function test_build_failed_ila_email_life_data()
    {
        $quoteType = QuoteTypes::LIFE;
        $managerEmails = ['manager1@test.com', 'manager2@test.com'];

        $result = $this->service->buildFailedIlaEmailData($quoteType, $managerEmails);

        $this->assertIsArray($result);
        $this->assertEquals($quoteType, $result['quoteType']);
        $this->assertEquals(WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS, $result['workflowType']);
        $this->assertEquals(now()->format('Y-m-d'), $result['dateOfAttempt']);
        $this->assertIsString($result['fileDownloadUrl']);
        $this->assertNotEmpty($result['fileDownloadUrl']);
    }

    public function test_build_failed_ila_email_pet_data()
    {
        $quoteType = QuoteTypes::PET;
        $managerEmails = ['manager1@test.com', 'manager2@test.com'];

        $result = $this->service->buildFailedIlaEmailData($quoteType, $managerEmails);

        $this->assertIsArray($result);
        $this->assertEquals($quoteType, $result['quoteType']);
        $this->assertEquals(WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS, $result['workflowType']);
        $this->assertEquals(now()->format('Y-m-d'), $result['dateOfAttempt']);
        $this->assertIsString($result['fileDownloadUrl']);
        $this->assertNotEmpty($result['fileDownloadUrl']);
    }

    public function test_build_failed_ila_email_cycle_data()
    {
        $quoteType = QuoteTypes::CYCLE;
        $managerEmails = ['manager1@test.com', 'manager2@test.com'];

        $result = $this->service->buildFailedIlaEmailData($quoteType, $managerEmails);

        $this->assertIsArray($result);
        $this->assertEquals($quoteType, $result['quoteType']);
        $this->assertEquals(WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS, $result['workflowType']);
        $this->assertEquals(now()->format('Y-m-d'), $result['dateOfAttempt']);
    }

    public function test_build_failed_ila_email_corpline_data()
    {
        $quoteType = QuoteTypes::GROUP_MEDICAL;
        $managerEmails = ['manager1@test.com', 'manager2@test.com'];

        $result = $this->service->buildFailedIlaEmailData($quoteType, $managerEmails);

        $this->assertIsArray($result);
        $this->assertEquals($quoteType, $result['quoteType']);
        $this->assertEquals(WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS, $result['workflowType']);
        $this->assertEquals(now()->format('Y-m-d'), $result['dateOfAttempt']);
        $this->assertIsString($result['fileDownloadUrl']);
        $this->assertNotEmpty($result['fileDownloadUrl']);
    }

    public function test_build_failed_ila_email_yacht_data()
    {
        $quoteType = QuoteTypes::YACHT;
        $managerEmails = ['manager1@test.com', 'manager2@test.com'];

        $result = $this->service->buildFailedIlaEmailData($quoteType, $managerEmails);

        $this->assertIsArray($result);
        $this->assertEquals($quoteType, $result['quoteType']);
        $this->assertEquals(WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS, $result['workflowType']);
        $this->assertEquals(now()->format('Y-m-d'), $result['dateOfAttempt']);
        $this->assertIsString($result['fileDownloadUrl']);
        $this->assertNotEmpty($result['fileDownloadUrl']);
    }

    public function test_build_failed_ila_email_group_medical_data()
    {
        $quoteType = QuoteTypes::GROUP_MEDICAL;
        $managerEmails = ['manager1@test.com', 'manager2@test.com'];

        $result = $this->service->buildFailedIlaEmailData($quoteType, $managerEmails);

        $this->assertIsArray($result);
        $this->assertEquals($quoteType, $result['quoteType']);
        $this->assertEquals(WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS, $result['workflowType']);
        $this->assertEquals(now()->format('Y-m-d'), $result['dateOfAttempt']);
        $this->assertIsString($result['fileDownloadUrl']);
        $this->assertNotEmpty($result['fileDownloadUrl']);
    }

    public function test_build_failed_ila_email_jetski_data()
    {
        $quoteType = QuoteTypes::JETSKI;
        $managerEmails = ['manager1@test.com', 'manager2@test.com'];

        $result = $this->service->buildFailedIlaEmailData($quoteType, $managerEmails);

        $this->assertIsArray($result);
        $this->assertEquals($quoteType, $result['quoteType']);
        $this->assertEquals(WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS, $result['workflowType']);
        $this->assertEquals(now()->format('Y-m-d'), $result['dateOfAttempt']);
        $this->assertIsString($result['fileDownloadUrl']);
        $this->assertNotEmpty($result['fileDownloadUrl']);
    }

}
