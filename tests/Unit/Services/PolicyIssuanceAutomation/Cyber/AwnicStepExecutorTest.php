<?php

use App\Enums\PolicyIssuanceEnum;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuance;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicApiService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicStepExecutor;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createCyberSchema();
    seedAwnicApplicationStorage();
});

afterEach(function () {
    Mockery::close();
});

it('marks insurer status when issue policy step fails', function () {
    $quote = PersonalQuote::factory()->cyberQuote()->withCyberDependencies()->create();
    $process = PolicyIssuance::withoutEvents(fn () => createAwnicPolicyIssuanceProcess($quote));

    $apiService = Mockery::mock(AwnicApiService::class);
    $apiService->shouldReceive('issuePolicy')->andReturn([
        'status' => false,
        'error' => 'failure',
        'message' => 'Policy failed',
    ]);

    $executor = new AwnicStepExecutor($apiService, Mockery::mock(AwnicBookPolicyService::class));
    $executor->executeIssuePolicyStep($quote, $process);

    $quote = $quote->fresh();

    expect($quote->insurer_api_status_id)->toBe(PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID)
        ->and($quote->api_issuance_status_id)->toBe(PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID);
});

it('marks insurer status when upload documents step fails', function () {
    $quote = PersonalQuote::factory()->cyberQuote()->withCyberDependencies()->create();
    $process = PolicyIssuance::withoutEvents(fn () => createAwnicPolicyIssuanceProcess($quote));

    $apiService = Mockery::mock(AwnicApiService::class);
    $apiService->shouldReceive('uploadDocuments')->andReturn([
        'status' => false,
        'error' => 'failure',
        'message' => 'Upload failed',
    ]);

    $executor = new AwnicStepExecutor($apiService, Mockery::mock(AwnicBookPolicyService::class));
    $executor->executeUploadDocumentsStep($quote, $process);

    $quote = $quote->fresh();

    expect($quote->insurer_api_status_id)->toBe(PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID)
        ->and($quote->api_issuance_status_id)->toBe(PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID);
});

it('marks insurer status when uploading policy documents to IMCRM fails', function () {
    $quote = PersonalQuote::factory()->cyberQuote()->withCyberDependencies()->create();
    $process = PolicyIssuance::withoutEvents(fn () => createAwnicPolicyIssuanceProcess($quote));

    $apiService = Mockery::mock(AwnicApiService::class);
    $apiService->shouldReceive('uploadPolicyDocumentsToIMCRM')->andReturn([
        'status' => false,
        'error' => 'failure',
        'message' => 'Upload to IMCRM failed',
    ]);

    $executor = new AwnicStepExecutor($apiService, Mockery::mock(AwnicBookPolicyService::class));
    $executor->executeUploadPolicyDocumentsStep($quote, $process);

    $quote = $quote->fresh();

    expect($quote->insurer_api_status_id)->toBe(PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID)
        ->and($quote->api_issuance_status_id)->toBe(PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID);
});
