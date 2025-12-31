<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\GenericDocumentTypeCode;
use App\Enums\InsuranceProviderEnum;
use App\Enums\QuoteTypeId;
use App\Models\BusinessTypeOfInsurance;
use App\Models\GenericDocument;
use App\Models\GenericDocumentType;
use App\Models\InsuranceProvider;
use App\Services\QuoteDocumentService;
use Tests\Helpers\TestSchemaCreator;
use Tests\TestCase;

class QuoteDocumentServiceTest extends TestCase
{
    protected QuoteDocumentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        TestSchemaCreator::createMinimalSchema();
        $this->service = new QuoteDocumentService();
    }

    public function test_get_claim_documents_returns_empty_structure_when_no_document_type_exists(): void
    {
        // Don't create any GenericDocumentType
        $result = $this->service->getClaimDocuments();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('Home', $result);
        $this->assertArrayHasKey('Travel', $result);
        $this->assertArrayHasKey('Business', $result);
        $this->assertEmpty($result['Home']['docs']);
        $this->assertEmpty($result['Travel']['docs']);
        $this->assertEmpty($result['Business']['docs']);
    }

    public function test_get_claim_documents_returns_empty_structure_when_no_documents_exist(): void
    {
        // Create GenericDocumentType but no documents
        GenericDocumentType::factory()->create([
            'code' => GenericDocumentTypeCode::CLAIM_FORM->value,
        ]);

        $result = $this->service->getClaimDocuments();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('Home', $result);
        $this->assertArrayHasKey('Travel', $result);
        $this->assertArrayHasKey('Business', $result);
        $this->assertEmpty($result['Home']['docs']);
        $this->assertEmpty($result['Travel']['docs']);
        $this->assertEmpty($result['Business']['docs']);
    }
}

