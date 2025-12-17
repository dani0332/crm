<?php

namespace Tests\Unit\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\AwnicEnum;
use App\Services\ApplicationStorageService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwniInsuranceService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicStepExecutor;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicValidationService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Mockery;
use Tests\Support\FakeApplicationStorageService;
use Tests\Support\FakeAwnicQuote;
use Tests\Support\FakePolicyIssuanceProcess;
use Tests\Support\FakePolicyIssuanceService;
use Tests\TestCase;

class AwniInsuranceServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->instance(ApplicationStorageService::class, new FakeApplicationStorageService());
        app()->instance(PolicyIssuanceService::class, new FakePolicyIssuanceService());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_executes_all_awni_steps_when_validation_passes(): void
    {
        $quote = new FakeAwnicQuote();
        $process = new FakePolicyIssuanceProcess($quote);

        $validation = Mockery::mock(AwnicValidationService::class);
        $validation->shouldReceive('validateRequiredData')
            ->once()
            ->with($quote)
            ->andReturn(['status' => true]);

        $stepExecutor = Mockery::mock(AwnicStepExecutor::class);
        $stepExecutor->shouldReceive('executeIssuePolicyStep')->once()->andReturn([
            'status' => true,
            'completed_step' => AwnicEnum::STEP_ISSUE_POLICY,
        ]);
        $stepExecutor->shouldReceive('executeUploadDocumentsStep')->once()->andReturn([
            'status' => true,
            'completed_step' => AwnicEnum::STEP_UPLOAD_DOCUMENTS,
        ]);
        $stepExecutor->shouldReceive('executeUploadPolicyDocumentsStep')->once()->andReturn([
            'status' => true,
            'completed_step' => AwnicEnum::STEP_UPLOAD_POLICY_DOCS,
        ]);
        $stepExecutor->shouldReceive('executeBookPolicyStep')->once()->andReturn([
            'status' => true,
            'completed_step' => AwnicEnum::STEP_BOOK_POLICY,
        ]);

        $service = new AwniInsuranceService(
            $stepExecutor,
            $validation,
            Mockery::mock(AwnicBookPolicyService::class),
            new AwnicResponseHandler()
        );

        $result = $service->executeSteps($process);

        $this->assertTrue($result['status']);
        $this->assertSame(AwnicEnum::STEP_BOOK_POLICY, $process->completed_step);
    }

    public function test_stops_execution_when_validation_fails(): void
    {
        $quote = new FakeAwnicQuote();
        $process = new FakePolicyIssuanceProcess($quote);

        $validation = Mockery::mock(AwnicValidationService::class);
        $validation->shouldReceive('validateRequiredData')
            ->once()
            ->with($quote)
            ->andReturn([
                'status' => false,
                'error' => 'Missing data',
                'message' => 'Missing data',
            ]);

        $stepExecutor = Mockery::mock(AwnicStepExecutor::class);
        $stepExecutor->shouldNotReceive('executeIssuePolicyStep');

        $service = new AwniInsuranceService(
            $stepExecutor,
            $validation,
            Mockery::mock(AwnicBookPolicyService::class),
            new AwnicResponseHandler()
        );

        $result = $service->executeSteps($process);

        $this->assertFalse($result['status']);
        $this->assertSame('Missing data', $result['error']);
    }

    public function test_propagates_step_failure_immediately(): void
    {
        $quote = new FakeAwnicQuote();
        $process = new FakePolicyIssuanceProcess($quote);

        $validation = Mockery::mock(AwnicValidationService::class);
        $validation->shouldReceive('validateRequiredData')
            ->once()
            ->andReturn(['status' => true]);

        $stepExecutor = Mockery::mock(AwnicStepExecutor::class);
        $stepExecutor->shouldReceive('executeIssuePolicyStep')->once()->andReturn([
            'status' => false,
            'error' => 'API failure',
            'message' => 'api fail',
        ]);
        $stepExecutor->shouldNotReceive('executeUploadDocumentsStep');

        $service = new AwniInsuranceService(
            $stepExecutor,
            $validation,
            Mockery::mock(AwnicBookPolicyService::class),
            new AwnicResponseHandler()
        );

        $result = $service->executeSteps($process);

        $this->assertFalse($result['status']);
        $this->assertSame('API failure', $result['error']);
    }
}

