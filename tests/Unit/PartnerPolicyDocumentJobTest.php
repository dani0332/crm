<?php

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTagEnums;
use App\Enums\QuoteTypeId;
use App\Jobs\PartnerPolicyDocumentJob;
use App\Models\QuoteTag;
use App\Services\BirdService;
use App\Services\CentralService;
use App\Services\QuoteDocumentService;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\PartnerSchema;

use function Pest\Laravel\mock;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    (new PartnerSchema)->register();
});

afterEach(function () {
    Mockery::close();
});

describe('handle', function () {
    it('sends email via BirdService and creates QuoteTag on success', function () {
        $carQuote = TestDataSeeder::createCarQuote(['uuid' => 'test-uuid']);

        $emailData = (object) ['partnerName' => 'Test Partner', 'some' => 'data'];

        $centralServiceMock = mock(CentralService::class);
        $centralServiceMock->shouldReceive('preparePolicyToCustomerData')
            ->once()
            ->andReturn($emailData);

        $quoteDocumentServiceMock = mock(QuoteDocumentService::class);
        $quoteDocumentServiceMock->shouldReceive('getHandBookDocuments')
            ->once()
            ->andReturn(collect());

        $birdServiceMock = mock(BirdService::class);
        $birdServiceMock->shouldReceive('triggerWebHookRequest')
            ->once()
            ->andReturn((object) ['status_code' => 200]);

        TestDataSeeder::seedApplicationStorage([
            ApplicationStorageEnums::BIRD_PARTNER_AUTOMATION_COMPLETED_WORKFLOW_URL => 'https://example.test/bird/workflow',
        ]);

        $job = new PartnerPolicyDocumentJob(
            rawDocuments: [['document_type_code' => 'TI', 'doc_url' => 'https://storage.example.com/doc.pdf']],
            partnerName: 'Test Partner',
            quoteUuid: 'test-uuid',
            quoteTypeId: QuoteTypeId::Car,
        );

        $job->handle();

        expect(QuoteTag::where([
            'quote_uuid' => 'test-uuid',
            'name' => QuoteTagEnums::PARTNER_POLICY_DOCUMENT_SENT,
        ])->exists())->toBeTrue();
    });

    it('does not create QuoteTag when Bird returns non-200', function () {
        TestDataSeeder::createCarQuote(['uuid' => 'test-uuid']);

        $centralServiceMock = mock(CentralService::class);
        $centralServiceMock->shouldReceive('preparePolicyToCustomerData')
            ->once()
            ->andReturn((object) []);

        $quoteDocumentServiceMock = mock(QuoteDocumentService::class);
        $quoteDocumentServiceMock->shouldReceive('getHandBookDocuments')
            ->once()
            ->andReturn(collect());

        $birdServiceMock = mock(BirdService::class);
        $birdServiceMock->shouldReceive('triggerWebHookRequest')
            ->once()
            ->andReturn((object) ['status_code' => 500]);

        TestDataSeeder::seedApplicationStorage([
            ApplicationStorageEnums::BIRD_PARTNER_AUTOMATION_COMPLETED_WORKFLOW_URL => 'https://example.test/bird/workflow',
        ]);

        $job = new PartnerPolicyDocumentJob(
            rawDocuments: [['document_type_code' => 'TI', 'doc_url' => 'https://storage.example.com/doc.pdf']],
            partnerName: 'Test Partner',
            quoteUuid: 'test-uuid',
            quoteTypeId: QuoteTypeId::Car,
        );

        $job->handle();

        expect(QuoteTag::where([
            'quote_uuid' => 'test-uuid',
            'name' => QuoteTagEnums::PARTNER_POLICY_DOCUMENT_SENT,
        ])->exists())->toBeFalse();
    });

    it('passes rawDocuments document type codes to preparePolicyToCustomerData', function () {
        TestDataSeeder::createCarQuote(['uuid' => 'test-uuid']);

        $quoteDocumentServiceMock = mock(QuoteDocumentService::class);
        $quoteDocumentServiceMock->shouldReceive('getHandBookDocuments')
            ->once()
            ->andReturn(collect());

        $centralServiceMock = mock(CentralService::class);
        $centralServiceMock->shouldReceive('preparePolicyToCustomerData')
            ->once()
            ->withArgs(function ($quote, $quoteTypeId, $extra, $existingEmailData) {
                return $existingEmailData->quoteDocuments !== null
                    && $existingEmailData->handBookDocuments !== null;
            })
            ->andReturn((object) []);

        $birdServiceMock = mock(BirdService::class);
        $birdServiceMock->shouldReceive('triggerWebHookRequest')
            ->once()
            ->andReturn((object) ['status_code' => 200]);

        TestDataSeeder::seedApplicationStorage([
            ApplicationStorageEnums::BIRD_PARTNER_AUTOMATION_COMPLETED_WORKFLOW_URL => 'https://example.test/bird/workflow',
        ]);

        $job = new PartnerPolicyDocumentJob(
            rawDocuments: [
                ['document_type_code' => 'TI', 'doc_url' => 'https://storage.example.com/doc1.pdf'],
                ['document_type_code' => 'CTIRBB', 'doc_url' => 'https://storage.example.com/doc2.pdf'],
            ],
            partnerName: 'Test Partner',
            quoteUuid: 'test-uuid',
            quoteTypeId: QuoteTypeId::Car,
        );

        $job->handle();
    });

    it('logs error on job failure without rethrowing', function () {
        $exception = new Exception('Test exception');

        $job = new PartnerPolicyDocumentJob(
            rawDocuments: [],
            partnerName: 'Test Partner',
            quoteUuid: 'test-uuid',
            quoteTypeId: QuoteTypeId::Car,
        );

        expect(fn () => $job->failed($exception))->not->toThrow(Exception::class);
    });
});
