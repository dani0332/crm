<?php

use App\Enums\AwnicEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\PolicyIssuanceEnum;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuanceLog;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicApiService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicDocumentHandler;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicHttpClient;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicQuoteUpdaterService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicRequestBuilder;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicValidationService;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createCyberSchema();
    seedAwnicApplicationStorage();
});

afterEach(function () {
    Mockery::close();
});

function makeHttpResponse(array $payload, int $status = 200): Response
{
    return new Response(new Psr7Response($status, [], json_encode($payload)));
}

it('issues policy and updates quote data on success', function () {
    $quote = PersonalQuote::factory()->withCyberDependencies()->create();
    $process = createAwnicPolicyIssuanceProcess($quote);

    $payload = ['payload' => true];
    $headers = ['X-Test' => 'foo'];

    $requestBuilder = Mockery::mock(AwnicRequestBuilder::class);
    $requestBuilder->shouldReceive('buildIssuePolicyPayload')->once()->andReturn($payload);
    $requestBuilder->shouldReceive('buildIssuePolicyHeaders')->once()->andReturn($headers);

    $responsePayload = [
        'isSuccess' => 'Y',
        'policyInfo' => [
            'policyNo' => 'POL-123',
            'policyStartDate' => '2024-01-01',
            'policyEndDate' => '2025-01-01',
            'premiumAmount' => 100,
            'prmVatAmt' => 5,
            'prmPayableAmt' => 105,
        ],
    ];
    $responseBodyObject = json_decode(json_encode($responsePayload));

    $client = Mockery::mock(AwnicHttpClient::class);
    $client->shouldReceive('post')->once()->andReturn(makeHttpResponse($responsePayload));
    $client->shouldReceive('getBaseUrl')->andReturn('https://awni.test');
    app()->instance('AwnicHttpClient', $client);

    $quoteUpdater = Mockery::mock(AwnicQuoteUpdaterService::class);
    $quoteUpdater->shouldReceive('updateQuoteFromIssuePolicyResponse')->once();
    $quoteUpdater->shouldReceive('updatePaymentFromIssuePolicyResponse')->once();

    $service = new AwnicApiService(
        $requestBuilder,
        new AwnicResponseHandler,
        Mockery::mock(AwnicDocumentHandler::class),
        $quoteUpdater,
        Mockery::mock(AwnicValidationService::class)
    );

    $result = $service->issuePolicy($quote, $process);

    expect($result['status'])->toBeTrue()
        ->and($result['completed_step'])->toBe(AwnicEnum::STEP_ISSUE_POLICY)
        ->and($result['data'])->toEqual($responseBodyObject);

    expect(
        PolicyIssuanceLog::where('policy_issuance_id', $process->id)
            ->where('step', AwnicEnum::STEP_ISSUE_POLICY)
            ->exists()
    )->toBeTrue();
});

it('returns failure when issue policy API responds with error', function () {
    $quote = PersonalQuote::factory()->withCyberDependencies()->create();
    $process = createAwnicPolicyIssuanceProcess($quote);

    $requestBuilder = Mockery::mock(AwnicRequestBuilder::class);
    $requestBuilder->shouldReceive('buildIssuePolicyPayload')->once()->andReturn([]);
    $requestBuilder->shouldReceive('buildIssuePolicyHeaders')->once()->andReturn([]);

    $client = Mockery::mock(AwnicHttpClient::class);
    $client->shouldReceive('post')->once()->andReturn(makeHttpResponse(['isSuccess' => 'N', 'errorList' => ['ERR']]));
    $client->shouldReceive('getBaseUrl')->andReturn('https://awni.test');
    app()->instance('AwnicHttpClient', $client);

    $quoteUpdater = Mockery::mock(AwnicQuoteUpdaterService::class);
    $quoteUpdater->shouldNotReceive('updateQuoteFromIssuePolicyResponse');

    $service = new AwnicApiService(
        $requestBuilder,
        new AwnicResponseHandler,
        Mockery::mock(AwnicDocumentHandler::class),
        $quoteUpdater,
        Mockery::mock(AwnicValidationService::class)
    );

    $result = $service->issuePolicy($quote, $process);

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->toEqual(['ERR']);

    expect(
        PolicyIssuanceLog::where('policy_issuance_id', $process->id)
            ->where('step', AwnicEnum::STEP_ISSUE_POLICY)
            ->where('status', PolicyIssuanceEnum::FAILED_STATUS)
            ->exists()
    )->toBeTrue();
});

it('skips upload documents call when validation fails', function () {
    $quote = PersonalQuote::factory()->withCyberDependencies()->create();
    $process = createAwnicPolicyIssuanceProcess($quote);

    $validation = Mockery::mock(AwnicValidationService::class);
    $validation->shouldReceive('validateUploadDocuments')->andReturn([
        'status' => false,
        'error' => 'Required documents not uploaded',
        'message' => 'Required documents not uploaded',
    ]);

    $documentHandler = Mockery::mock(AwnicDocumentHandler::class);
    $documentHandler->shouldReceive('getDocumentByType')->andReturn(null);

    $service = new AwnicApiService(
        Mockery::mock(AwnicRequestBuilder::class),
        new AwnicResponseHandler,
        $documentHandler,
        Mockery::mock(AwnicQuoteUpdaterService::class),
        $validation
    );

    $result = $service->uploadDocuments($quote, $process);

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->toBe('Required documents not uploaded');
});

it('uploads documents and records policy issuance log', function () {
    $quote = PersonalQuote::factory()->withCyberDependencies()->create();
    $process = createAwnicPolicyIssuanceProcess($quote);

    $documentHandler = Mockery::mock(AwnicDocumentHandler::class);
    $documentHandler->shouldReceive('getDocumentByType')->andReturn([
        [
            'document_type_code' => DocumentTypeCode::CYB_EID,
            'doc_url' => 'documents/eid.pdf',
            'doc_name' => 'EID.pdf',
        ],
    ]);
    $documentHandler->shouldReceive('getDocTypeCodeForCyber')->andReturn('4');
    $documentHandler->shouldReceive('fetchDocumentContent')->andReturn(['status' => true, 'content' => 'binary']);

    $validation = Mockery::mock(AwnicValidationService::class);
    $validation->shouldReceive('validateUploadDocuments')->andReturn(['status' => true]);

    $requestBuilder = Mockery::mock(AwnicRequestBuilder::class);
    $requestBuilder->shouldReceive('buildUploadDocumentsPayload')->andReturn(['payload' => true]);

    $client = Mockery::mock(AwnicHttpClient::class);
    $client->shouldReceive('post')->andReturn(makeHttpResponse(['isSuccess' => 'Y']));
    $client->shouldReceive('getBaseUrl')->andReturn('https://awni.test');
    app()->instance('AwnicHttpClient', $client);

    $service = new AwnicApiService(
        $requestBuilder,
        new AwnicResponseHandler,
        $documentHandler,
        Mockery::mock(AwnicQuoteUpdaterService::class),
        $validation
    );

    $response = $service->uploadDocuments($quote, $process);

    expect($response['status'])->toBeTrue()
        ->and(
            PolicyIssuanceLog::where('policy_issuance_id', $process->id)
                ->where('step', AwnicEnum::STEP_UPLOAD_DOCUMENTS)
                ->exists()
        )->toBeTrue();
});

it('logs failure when document upload API fails', function () {
    $quote = PersonalQuote::factory()->withCyberDependencies()->create();
    $process = createAwnicPolicyIssuanceProcess($quote);

    $documentHandler = Mockery::mock(AwnicDocumentHandler::class);
    $documentHandler->shouldReceive('getDocumentByType')->andReturn([
        [
            'document_type_code' => DocumentTypeCode::CYB_EID,
            'doc_url' => 'documents/eid.pdf',
            'doc_name' => 'EID.pdf',
        ],
    ]);
    $documentHandler->shouldReceive('getDocTypeCodeForCyber')->andReturn('4');
    $documentHandler->shouldReceive('fetchDocumentContent')->andReturn(['status' => true, 'content' => 'binary']);

    $validation = Mockery::mock(AwnicValidationService::class);
    $validation->shouldReceive('validateUploadDocuments')->andReturn(['status' => true]);

    $requestBuilder = Mockery::mock(AwnicRequestBuilder::class);
    $requestBuilder->shouldReceive('buildUploadDocumentsPayload')->andReturn(['payload' => true]);

    $client = Mockery::mock(AwnicHttpClient::class);
    $client->shouldReceive('post')->andReturn(makeHttpResponse(['isSuccess' => 'N', 'errorList' => ['ERR']]));
    $client->shouldReceive('getBaseUrl')->andReturn('https://awni.test');
    app()->instance('AwnicHttpClient', $client);

    $service = new AwnicApiService(
        $requestBuilder,
        new AwnicResponseHandler,
        $documentHandler,
        Mockery::mock(AwnicQuoteUpdaterService::class),
        $validation
    );

    $result = $service->uploadDocuments($quote, $process);

    expect($result['status'])->toBeFalse()
        ->and(
            PolicyIssuanceLog::where('policy_issuance_id', $process->id)
                ->where('step', AwnicEnum::STEP_UPLOAD_DOCUMENTS)
                ->where('status', PolicyIssuanceEnum::FAILED_STATUS)
                ->exists()
        )->toBeTrue();
});

it('uploads policy documents to IMCRM and records log entries', function () {
    $quote = PersonalQuote::factory()->withCyberDependencies()->create([
        'insurer_tax_invoice_doc_id' => 'DOC-TAX',
        'insurer_debit_note_doc_id' => 'DOC-DN',
        'insurer_policy_doc_id' => 'DOC-POL',
    ]);
    $process = createAwnicPolicyIssuanceProcess($quote);

    $documentHandler = Mockery::mock(AwnicDocumentHandler::class);
    $documentHandler->shouldReceive('getDocTypeCodeForIMCRM')->andReturn([
        DocumentTypeCode::CYB_TI => 'DOC-TAX',
        DocumentTypeCode::CYB_TIRBB => 'DOC-DN',
        DocumentTypeCode::CYB_PS => 'DOC-POL',
    ]);
    $documentHandler->shouldReceive('uploadAndAttachToQuoteDocuments')
        ->andReturn((object) ['id' => 10], (object) ['id' => 11], (object) ['id' => 12]);

    $validation = Mockery::mock(AwnicValidationService::class);
    $validation->shouldReceive('validateDownloadDocuments')->andReturn(['status' => true]);

    $requestBuilder = Mockery::mock(AwnicRequestBuilder::class);
    $requestBuilder->shouldReceive('buildDownloadDocumentPayload')->andReturn(['payload' => true]);

    $payload = [
        'isSuccess' => 'Y',
        'documentContent' => [
            'documentContent' => base64_encode('binary'),
            'documentName' => 'Policy.pdf',
        ],
    ];

    $client = Mockery::mock(AwnicHttpClient::class);
    $client->shouldReceive('post')->andReturn(
        makeHttpResponse($payload),
        makeHttpResponse($payload),
        makeHttpResponse($payload)
    );
    $client->shouldReceive('getBaseUrl')->andReturn('https://awni.test');
    app()->instance('AwnicHttpClient', $client);

    $service = new AwnicApiService(
        $requestBuilder,
        new AwnicResponseHandler,
        $documentHandler,
        Mockery::mock(AwnicQuoteUpdaterService::class),
        $validation
    );

    $response = $service->uploadPolicyDocumentsToIMCRM($quote, $process);

    expect($response['status'])->toBeTrue()
        ->and(
            PolicyIssuanceLog::where('policy_issuance_id', $process->id)
                ->where('step', AwnicEnum::STEP_UPLOAD_POLICY_DOCS)
                ->exists()
        )->toBeTrue();
});

it('logs failure when insurer document download fails', function () {
    $quote = PersonalQuote::factory()->withCyberDependencies()->create([
        'insurer_tax_invoice_doc_id' => 'DOC-TAX',
        'insurer_debit_note_doc_id' => 'DOC-DN',
        'insurer_policy_doc_id' => 'DOC-POL',
    ]);
    $process = createAwnicPolicyIssuanceProcess($quote);

    $documentHandler = Mockery::mock(AwnicDocumentHandler::class);
    $documentHandler->shouldReceive('getDocTypeCodeForIMCRM')->andReturn([
        DocumentTypeCode::CYB_TI => 'DOC-TAX',
    ]);

    $validation = Mockery::mock(AwnicValidationService::class);
    $validation->shouldReceive('validateDownloadDocuments')->andReturn(['status' => true]);

    $requestBuilder = Mockery::mock(AwnicRequestBuilder::class);
    $requestBuilder->shouldReceive('buildDownloadDocumentPayload')->andReturn(['payload' => true]);

    $client = Mockery::mock(AwnicHttpClient::class);
    $client->shouldReceive('post')->andReturn(makeHttpResponse(['isSuccess' => 'N', 'errorList' => ['ERR']]));
    $client->shouldReceive('getBaseUrl')->andReturn('https://awni.test');
    app()->instance('AwnicHttpClient', $client);

    $service = new AwnicApiService(
        $requestBuilder,
        new AwnicResponseHandler,
        $documentHandler,
        Mockery::mock(AwnicQuoteUpdaterService::class),
        $validation
    );

    $result = $service->uploadPolicyDocumentsToIMCRM($quote, $process);

    expect($result['status'])->toBeFalse()
        ->and(
            PolicyIssuanceLog::where('policy_issuance_id', $process->id)
                ->where('step', AwnicEnum::STEP_UPLOAD_POLICY_DOCS)
                ->where('status', PolicyIssuanceEnum::FAILED_STATUS)
                ->exists()
        )->toBeTrue();
});
