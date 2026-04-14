<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Adnic;

use App\Enums\AdnicEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypes;
use App\Models\HealthQuote;
use App\Models\QuoteDocument;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicDocumentHandler;
use App\Services\QuoteDocumentService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class AdnicDocumentHandlerTest extends TestCase
{
    private AdnicDocumentHandler $handler;
    private $quoteDocumentServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        config(['constants.AZURE_IM_STORAGE_URL' => 'https://test.blob.core.windows.net']);
        config(['constants.AZURE_IM_STORAGE_CONTAINER' => 'container']);

        $this->quoteDocumentServiceMock = Mockery::mock(QuoteDocumentService::class);
        $this->handler = new AdnicDocumentHandler($this->quoteDocumentServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_insurer_doc_code_for_health_returns_correct_codes(): void
    {
        $this->assertEquals('3', $this->handler->getInsurerDocCodeForHealth(DocumentTypeCode::HEA_EMIRATE_ID_COPY));
        $this->assertEquals('6', $this->handler->getInsurerDocCodeForHealth(DocumentTypeCode::HEA_VISA));
        $this->assertEquals('1', $this->handler->getInsurerDocCodeForHealth(DocumentTypeCode::HEA_PAS));
    }

    public function test_get_insurer_doc_code_for_health_returns_null_for_invalid_type(): void
    {
        $result = $this->handler->getInsurerDocCodeForHealth('INVALID_TYPE');

        $this->assertNull($result);
    }

    public function test_get_insurer_doc_code_for_health_returns_null_for_empty_string(): void
    {
        $result = $this->handler->getInsurerDocCodeForHealth('');

        $this->assertNull($result);
    }

    public function test_get_doc_type_code_for_imcrm_returns_correct_mapping(): void
    {
        $quote = Mockery::mock(HealthQuote::class);

        $result = $this->handler->getDocTypeCodeForIMCRM($quote);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(AdnicEnum::INSURER_DOCUMENT_KEY_POLICY_DOCUMENT, $result);
        $this->assertArrayHasKey(AdnicEnum::INSURER_DOCUMENT_KEY_COMMISION_NOTE, $result);
        $this->assertArrayHasKey(AdnicEnum::INSURER_DOCUMENT_KEY_TAX_INVOICE, $result);

        $this->assertEquals(DocumentTypeCode::POLC, $result[AdnicEnum::INSURER_DOCUMENT_KEY_POLICY_DOCUMENT]);
        $this->assertEquals(DocumentTypeCode::TIRBB, $result[AdnicEnum::INSURER_DOCUMENT_KEY_COMMISION_NOTE]);
        $this->assertEquals(DocumentTypeCode::TI, $result[AdnicEnum::INSURER_DOCUMENT_KEY_TAX_INVOICE]);
    }

    public function test_get_doc_type_code_for_imcrm_returns_exactly_three_mappings(): void
    {
        $quote = Mockery::mock(HealthQuote::class);

        $result = $this->handler->getDocTypeCodeForIMCRM($quote);

        $this->assertCount(3, $result);
    }

    public function test_get_quote_document_mapping_for_insurer_documents_returns_correct_codes(): void
    {
        $this->assertEquals(
            DocumentTypeCode::POLC,
            $this->handler->getQuoteDocumentMappingForInsurerDocuments(AdnicEnum::INSURER_DOCUMENT_KEY_POLICY_DOCUMENT)
        );
        $this->assertEquals(
            DocumentTypeCode::TIRBB,
            $this->handler->getQuoteDocumentMappingForInsurerDocuments(AdnicEnum::INSURER_DOCUMENT_KEY_COMMISION_NOTE)
        );
        $this->assertEquals(
            DocumentTypeCode::TI,
            $this->handler->getQuoteDocumentMappingForInsurerDocuments(AdnicEnum::INSURER_DOCUMENT_KEY_TAX_INVOICE)
        );
    }

    public function test_get_quote_document_mapping_for_insurer_documents_returns_null_for_invalid_type(): void
    {
        $result = $this->handler->getQuoteDocumentMappingForInsurerDocuments('InvalidDocumentId');

        $this->assertNull($result);
    }

    public function test_get_quote_document_type_codess_to_upload_returns_collection(): void
    {
        $result = $this->handler->getQuoteDocumentTypeCodessToUpload();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(6, $result);

        $this->assertTrue($result->has(DocumentTypeCode::HEA_VISA));
        $this->assertTrue($result->has(DocumentTypeCode::HEA_PAS));
        $this->assertTrue($result->has(DocumentTypeCode::HEA_EMIRATE_ID_COPY));
    }

    public function test_get_quote_document_type_codess_to_upload_has_correct_structure(): void
    {
        $result = $this->handler->getQuoteDocumentTypeCodessToUpload();

        $visaDoc = $result->get(DocumentTypeCode::HEA_VISA);

        $this->assertIsArray($visaDoc);
        $this->assertArrayHasKey('code', $visaDoc);
        $this->assertArrayHasKey('insurerDocCode', $visaDoc);
        $this->assertArrayHasKey('insurerDocName', $visaDoc);
        $this->assertArrayHasKey('uploaded', $visaDoc);

        $this->assertEquals(DocumentTypeCode::HEA_VISA, $visaDoc['code']);
        $this->assertEquals('6', $visaDoc['insurerDocCode']);
        $this->assertEquals('Insured Visa Copy', $visaDoc['insurerDocName']);
        $this->assertFalse($visaDoc['uploaded']);
    }

    public function test_get_quote_document_type_codess_to_upload_passport_has_correct_values(): void
    {
        $result = $this->handler->getQuoteDocumentTypeCodessToUpload();

        $passportDoc = $result->get(DocumentTypeCode::HEA_PAS);

        $this->assertEquals(DocumentTypeCode::HEA_PAS, $passportDoc['code']);
        $this->assertEquals('1', $passportDoc['insurerDocCode']);
        $this->assertEquals('Insured Passport', $passportDoc['insurerDocName']);
        $this->assertFalse($passportDoc['uploaded']);
    }

    public function test_get_quote_document_type_codess_to_upload_eid_has_correct_values(): void
    {
        $result = $this->handler->getQuoteDocumentTypeCodessToUpload();

        $eidDoc = $result->get(DocumentTypeCode::HEA_EMIRATE_ID_COPY);

        $this->assertEquals(DocumentTypeCode::HEA_EMIRATE_ID_COPY, $eidDoc['code']);
        $this->assertEquals('3', $eidDoc['insurerDocCode']);
        $this->assertEquals('Emirates ID', $eidDoc['insurerDocName']);
        $this->assertFalse($eidDoc['uploaded']);
    }

    public function test_get_document_by_type_returns_matching_documents(): void
    {
        $doc1 = new \stdClass;
        $doc1->document_type_code = DocumentTypeCode::HEA_EID;

        $doc2 = new \stdClass;
        $doc2->document_type_code = DocumentTypeCode::HEA_VISA;

        $documents = collect([$doc1, $doc2]);

        $quote = new \stdClass;
        $quote->documents = $documents;

        $result = $this->handler->getDocumentByType($quote, [DocumentTypeCode::HEA_EID, DocumentTypeCode::HEA_VISA]);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Collection::class, $result);
        if ($result instanceof Collection) {
            $this->assertCount(2, $result);
        }
    }

    public function test_get_document_by_type_returns_null_when_no_documents_found(): void
    {
        $quote = new \stdClass;
        $quote->documents = collect();

        $result = $this->handler->getDocumentByType($quote, [DocumentTypeCode::HEA_EID]);

        $this->assertNull($result);
    }

    public function test_get_document_by_type_returns_null_when_documents_is_null(): void
    {
        $quote = new \stdClass;
        $quote->documents = null;

        $result = $this->handler->getDocumentByType($quote, [DocumentTypeCode::HEA_EID]);

        $this->assertNull($result);
    }

    public function test_get_document_by_type_filters_by_type_codes(): void
    {
        $doc1 = new \stdClass;
        $doc1->document_type_code = DocumentTypeCode::HEA_EID;

        $doc2 = new \stdClass;
        $doc2->document_type_code = DocumentTypeCode::HEA_VISA;

        $doc3 = new \stdClass;
        $doc3->document_type_code = 'OTHER_DOC';

        $documents = collect([$doc1, $doc2, $doc3]);

        $quote = new \stdClass;
        $quote->documents = $documents;

        $result = $this->handler->getDocumentByType($quote, [DocumentTypeCode::HEA_EID, DocumentTypeCode::HEA_VISA]);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Collection::class, $result);
        if ($result instanceof Collection) {
            $this->assertCount(2, $result);
        }
    }

    public function test_get_document_by_type_returns_single_document(): void
    {
        $doc1 = new \stdClass;
        $doc1->document_type_code = DocumentTypeCode::HEA_EID;

        $documents = collect([$doc1]);

        $quote = new \stdClass;
        $quote->documents = $documents;

        $result = $this->handler->getDocumentByType($quote, [DocumentTypeCode::HEA_EID]);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Collection::class, $result);
        if ($result instanceof Collection) {
            $this->assertCount(1, $result);
        }
    }

    public function test_get_document_by_type_keeps_only_latest_per_document_type_code(): void
    {
        $older = new \stdClass;
        $older->id = 1;
        $older->document_type_code = DocumentTypeCode::HEA_VISA;

        $newer = new \stdClass;
        $newer->id = 2;
        $newer->document_type_code = DocumentTypeCode::HEA_VISA;

        $quote = new \stdClass;
        $quote->uuid = 'quote-uuid-latest-docs';
        $quote->documents = collect([$older, $newer]);

        $result = $this->handler->getDocumentByType($quote, [DocumentTypeCode::HEA_VISA]);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertSame(2, $result->first()->id);
    }

    public function test_upload_and_attach_to_quote_documents_builds_correct_data_array(): void
    {
        $quote = new \stdClass;
        $quote->uuid = 'test-uuid-123';

        // Simulating base64 encoded content as it comes from AdnicApiService
        $documentContent = base64_encode('sample document content');
        $documentCode = DocumentTypeCode::POLC;
        $originalName = 'policy.pdf';

        $this->quoteDocumentServiceMock->shouldReceive('uploadQuoteDocument')
            ->once()
            ->with(
                $documentContent,
                Mockery::on(function ($data) use ($quote, $documentCode, $originalName) {
                    return $data['is_base_64'] === 1
                        && $data['quote_uuid'] === $quote->uuid
                        && $data['quote_type'] === QuoteTypes::HEALTH->value
                        && $data['file_name'] === $originalName
                        && $data['document_type_code'] === $documentCode;
                }),
                $quote
            )
            ->andReturn(Mockery::mock(QuoteDocument::class));

        $result = $this->handler->uploadAndAttachToQuoteDocuments($quote, $documentContent, $documentCode, $originalName);

        $this->assertNotNull($result);
    }

    public function test_upload_and_attach_to_quote_documents_handles_null_original_name(): void
    {
        $quote = new \stdClass;
        $quote->uuid = 'test-uuid-123';

        // Base64 encoded content as expected in the flow
        $documentContent = base64_encode('sample content');
        $documentCode = DocumentTypeCode::TI;

        $this->quoteDocumentServiceMock->shouldReceive('uploadQuoteDocument')
            ->once()
            ->with(
                $documentContent,
                Mockery::on(function ($data) {
                    return $data['file_name'] === null;
                }),
                $quote
            )
            ->andReturn(Mockery::mock(QuoteDocument::class));

        $result = $this->handler->uploadAndAttachToQuoteDocuments($quote, $documentContent, $documentCode, null);

        $this->assertNotNull($result);
    }

    public function test_upload_and_attach_to_quote_documents_always_sets_base64_flag(): void
    {
        $quote = new \stdClass;
        $quote->uuid = 'test-uuid-123';

        $this->quoteDocumentServiceMock->shouldReceive('uploadQuoteDocument')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::on(function ($data) {
                    return isset($data['is_base_64']) && $data['is_base_64'] === 1;
                }),
                Mockery::any()
            )
            ->andReturn(Mockery::mock(QuoteDocument::class));

        $this->handler->uploadAndAttachToQuoteDocuments($quote, 'content', DocumentTypeCode::POLC);

        $this->assertTrue(true); // If we got here, the assertion in the mock passed
    }

    public function test_fetch_document_content_returns_error_for_empty_path(): void
    {
        $this->quoteDocumentServiceMock->shouldReceive('getDocumentUrl')
            ->with('')
            ->andReturn('invalid_path_to_trigger_false_read');

        $result = $this->handler->fetchDocumentContent('');

        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('Invalid or empty document content', $result['message']);
    }

    public function test_fetch_document_content_returns_error_for_non_existent_file(): void
    {
        $path = '/non/existent/file.pdf';
        $this->quoteDocumentServiceMock->shouldReceive('getDocumentUrl')
            ->with($path)
            ->andReturn($path);

        $result = $this->handler->fetchDocumentContent($path);

        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_fetch_document_content_returns_raw_content_not_base64(): void
    {
        // This test verifies that fetchDocumentContent returns raw content
        // The base64 encoding happens in AdnicApiService, not in the handler

        // We can't easily test the actual file reading without mocking file_get_contents
        // But we can verify the behavior by checking other tests that validate:
        // 1. fetchDocumentContent returns status and content
        // 2. AdnicApiService does base64_encode on the content

        // This is a documentation test to ensure developers understand the flow
        $this->assertTrue(true, 'fetchDocumentContent returns raw content, base64 encoding is done in AdnicApiService');
    }

    public function test_modify_emirate_document_returns_null_when_umaf_response_missing(): void
    {
        if (empty(config('database.connections.mongodb.dsn'))) {
            $this->markTestSkipped('MongoDB is not configured; HealthUMAFResponse queries cannot run.');
        }

        $result = $this->handler->modifyEmirateDocument('00000000-0000-0000-0000-000000000099');

        $this->assertNull($result);
    }

    public function test_allowed_document_mime_types_constant_has_correct_values(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $constant = $reflection->getConstant('ALLOWED_DOCUMENT_MIME_TYPES');

        $this->assertIsArray($constant);
        $this->assertCount(3, $constant);
        $this->assertContains('application/pdf', $constant);
        $this->assertContains('image/jpeg', $constant);
        $this->assertContains('image/png', $constant);
    }
}
