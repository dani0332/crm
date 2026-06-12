<?php

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Jobs\PartnerPolicyDocumentJob;
use App\Services\BirdService;
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

describe('handle - buildDocumentPayload', function () {
    it('generates fresh URLs at job execution time using doc_url', function () {
        $quoteDocumentServiceMock = Mockery::mock(QuoteDocumentService::class)->makePartial();
        $quoteDocumentServiceMock
            ->shouldReceive('getDocumentUrl')
            ->with('https://storage.example.com/doc.pdf', 'azureIMPrivate')
            ->once()
            ->andReturn('https://azure.example.com/fresh-url.pdf');

        $birdServiceMock = mock(BirdService::class);
        $birdServiceMock->shouldReceive('triggerWebHookRequest')
            ->once()
            ->withArgs(function ($url, $emailData) {
                return $emailData->{DocumentTypeCode::TI} === 'https://azure.example.com/fresh-url.pdf'
                    && $emailData->{'EXT_'.DocumentTypeCode::TI} === 'pdf'
                    && $emailData->partnerEmail === 'partner@example.com';
            })
            ->andReturn((object) ['status_code' => 200]);

        TestDataSeeder::seedApplicationStorage([
            ApplicationStorageEnums::BIRD_PARTNER_AUTOMATION_COMPLETED_WORKFLOW_URL => 'https://example.test/bird/workflow',
        ]);

        $job = new PartnerPolicyDocumentJob(
            rawDocuments: [
                [
                    'document_type_code' => DocumentTypeCode::TI,
                    'doc_url' => 'https://storage.example.com/doc.pdf',
                ],
            ],
            partnerEmail: 'partner@example.com',
            quoteUuid: 'test-uuid',
            quoteTypeId: 1,
        );

        $job->handle($quoteDocumentServiceMock);
    });

    it('prefers watermarked_doc_url over doc_url when generating URLs', function () {
        $quoteDocumentServiceMock = Mockery::mock(QuoteDocumentService::class)->makePartial();
        $quoteDocumentServiceMock
            ->shouldReceive('getDocumentUrl')
            ->with('https://storage.example.com/watermarked.pdf', 'azureIMPrivate')
            ->once()
            ->andReturn('https://azure.example.com/fresh-watermarked.pdf');

        $birdServiceMock = mock(BirdService::class);
        $birdServiceMock->shouldReceive('triggerWebHookRequest')
            ->once()
            ->withArgs(function ($url, $emailData) {
                return $emailData->{DocumentTypeCode::TI} === 'https://azure.example.com/fresh-watermarked.pdf';
            })
            ->andReturn((object) ['status_code' => 200]);

        TestDataSeeder::seedApplicationStorage([
            ApplicationStorageEnums::BIRD_PARTNER_AUTOMATION_COMPLETED_WORKFLOW_URL => 'https://example.test/bird/workflow',
        ]);

        $job = new PartnerPolicyDocumentJob(
            rawDocuments: [
                [
                    'document_type_code' => DocumentTypeCode::TI,
                    'doc_url' => 'https://storage.example.com/original.pdf',
                    'watermarked_doc_url' => 'https://storage.example.com/watermarked.pdf',
                ],
            ],
            partnerEmail: 'partner@example.com',
            quoteUuid: 'test-uuid',
            quoteTypeId: 1,
        );

        $job->handle($quoteDocumentServiceMock);
    });

    it('uses empty string when URL generation returns null', function () {
        $quoteDocumentServiceMock = Mockery::mock(QuoteDocumentService::class)->makePartial();
        $quoteDocumentServiceMock->shouldReceive('getDocumentUrl')->once()->andReturn(null);

        $birdServiceMock = mock(BirdService::class);
        $birdServiceMock->shouldReceive('triggerWebHookRequest')
            ->once()
            ->withArgs(function ($url, $emailData) {
                return $emailData->{DocumentTypeCode::TI} === '';
            })
            ->andReturn((object) ['status_code' => 200]);

        TestDataSeeder::seedApplicationStorage([
            ApplicationStorageEnums::BIRD_PARTNER_AUTOMATION_COMPLETED_WORKFLOW_URL => 'https://example.test/bird/workflow',
        ]);

        $job = new PartnerPolicyDocumentJob(
            rawDocuments: [
                [
                    'document_type_code' => DocumentTypeCode::TI,
                    'doc_url' => 'https://storage.example.com/doc.pdf',
                ],
            ],
            partnerEmail: 'partner@example.com',
            quoteUuid: 'test-uuid',
            quoteTypeId: 1,
        );

        $job->handle($quoteDocumentServiceMock);
    });

    it('generates payload for multiple documents', function () {
        $quoteDocumentServiceMock = Mockery::mock(QuoteDocumentService::class)->makePartial();
        $quoteDocumentServiceMock->shouldReceive('getDocumentUrl')->twice()->andReturn('https://azure.example.com/doc.pdf');

        $birdServiceMock = mock(BirdService::class);
        $birdServiceMock->shouldReceive('triggerWebHookRequest')
            ->once()
            ->withArgs(function ($url, $emailData) {
                return property_exists($emailData, DocumentTypeCode::TI)
                    && property_exists($emailData, DocumentTypeCode::CTIRBB);
            })
            ->andReturn((object) ['status_code' => 200]);

        TestDataSeeder::seedApplicationStorage([
            ApplicationStorageEnums::BIRD_PARTNER_AUTOMATION_COMPLETED_WORKFLOW_URL => 'https://example.test/bird/workflow',
        ]);

        $job = new PartnerPolicyDocumentJob(
            rawDocuments: [
                ['document_type_code' => DocumentTypeCode::TI, 'doc_url' => 'https://storage.example.com/doc1.pdf'],
                ['document_type_code' => DocumentTypeCode::CTIRBB, 'doc_url' => 'https://storage.example.com/doc2.pdf'],
            ],
            partnerEmail: 'partner@example.com',
            quoteUuid: 'test-uuid',
            quoteTypeId: 1,
        );

        $job->handle($quoteDocumentServiceMock);
    });
});
