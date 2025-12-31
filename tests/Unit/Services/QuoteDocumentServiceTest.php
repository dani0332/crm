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

    public function test_get_claim_documents_groups_documents_by_quote_type(): void
    {
        $documentType = GenericDocumentType::factory()->create([
            'code' => GenericDocumentTypeCode::CLAIM_FORM->value,
        ]);

        $insuranceProvider = InsuranceProvider::factory()->create([
            'code' => InsuranceProviderEnum::RSA->value,
        ]);

        GenericDocument::factory()->forHome()->create([
            'documentable_type' => GenericDocumentType::class,
            'documentable_id' => $documentType->id,
            'insurance_provider_id' => $insuranceProvider->id,
            'name' => 'test1.pdf',
            'path' => 'documents/claims/test1.pdf',
        ]);

        GenericDocument::factory()->forTravel()->create([
            'documentable_type' => GenericDocumentType::class,
            'documentable_id' => $documentType->id,
            'insurance_provider_id' => $insuranceProvider->id,
            'name' => 'test2.pdf',
            'path' => 'documents/claims/test2.pdf',
        ]);

        $result = $this->service->getClaimDocuments();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('Home', $result);
        $this->assertArrayHasKey('Travel', $result);
        $this->assertCount(1, $result['Home']['docs']);
        $this->assertCount(1, $result['Travel']['docs']);
        $this->assertEquals(QuoteTypeId::Home, $result['Home']['quoteTypeId']);
        $this->assertEquals(QuoteTypeId::Travel, $result['Travel']['quoteTypeId']);
    }

    public function test_get_claim_documents_includes_business_type_only_for_business_lob(): void
    {
        $documentType = GenericDocumentType::factory()->create([
            'code' => GenericDocumentTypeCode::CLAIM_FORM->value,
        ]);

        $insuranceProvider = InsuranceProvider::factory()->create();
        $businessType = BusinessTypeOfInsurance::factory()->create(['text' => 'Property']);

        // Business document with business type
        GenericDocument::factory()->create([
            'documentable_type' => GenericDocumentType::class,
            'documentable_id' => $documentType->id,
            'quote_type_id' => QuoteTypeId::Business,
            'insurance_provider_id' => $insuranceProvider->id,
            'business_type_of_insurance_id' => $businessType->id,
            'name' => 'business.pdf',
        ]);

        // Home document (should NOT have business type even if set)
        GenericDocument::factory()->forHome()->create([
            'documentable_type' => GenericDocumentType::class,
            'documentable_id' => $documentType->id,
            'insurance_provider_id' => $insuranceProvider->id,
            'business_type_of_insurance_id' => $businessType->id,
            'name' => 'home.pdf',
        ]);

        $result = $this->service->getClaimDocuments();

        // Business document should have business_type_of_insurance
        $businessDoc = $result['Business']['docs'][0];
        $this->assertArrayHasKey('businessTypeOfInsuranceId', $businessDoc);
        $this->assertArrayHasKey('businessTypeOfInsurance', $businessDoc);
        $this->assertEquals($businessType->id, $businessDoc['businessTypeOfInsuranceId']);
        $this->assertNotNull($businessDoc['businessTypeOfInsurance']);
        $this->assertEquals('Property', $businessDoc['businessTypeOfInsurance']['text']);

        // Home document should NOT have business_type_of_insurance
        $homeDoc = $result['Home']['docs'][0];
        $this->assertArrayNotHasKey('businessTypeOfInsuranceId', $homeDoc);
        $this->assertArrayNotHasKey('businessTypeOfInsurance', $homeDoc);
    }

    public function test_get_claim_documents_skips_documents_without_insurance_provider(): void
    {
        $documentType = GenericDocumentType::factory()->create([
            'code' => GenericDocumentTypeCode::CLAIM_FORM->value,
        ]);

        $insuranceProvider = InsuranceProvider::factory()->create();

        // Document with insurance provider
        GenericDocument::factory()->forHome()->create([
            'documentable_type' => GenericDocumentType::class,
            'documentable_id' => $documentType->id,
            'insurance_provider_id' => $insuranceProvider->id,
            'name' => 'with-provider.pdf',
        ]);

        // Document without insurance provider
        GenericDocument::factory()->forHome()->create([
            'documentable_type' => GenericDocumentType::class,
            'documentable_id' => $documentType->id,
            'insurance_provider_id' => null,
            'name' => 'without-provider.pdf',
        ]);

        $result = $this->service->getClaimDocuments();

        // Only document with insurance provider should be included
        $this->assertCount(1, $result['Home']['docs']);
        $this->assertEquals('with-provider.pdf', $result['Home']['docs'][0]['docTitle']);
    }

    public function test_get_claim_documents_skips_documents_with_null_quote_type_id(): void
    {
        $documentType = GenericDocumentType::factory()->create([
            'code' => GenericDocumentTypeCode::CLAIM_FORM->value,
        ]);

        $insuranceProvider = InsuranceProvider::factory()->create();

        // Document with quote_type_id
        GenericDocument::factory()->forHome()->create([
            'documentable_type' => GenericDocumentType::class,
            'documentable_id' => $documentType->id,
            'insurance_provider_id' => $insuranceProvider->id,
            'name' => 'with-quote-type.pdf',
        ]);

        // Document without quote_type_id
        GenericDocument::factory()->create([
            'documentable_type' => GenericDocumentType::class,
            'documentable_id' => $documentType->id,
            'quote_type_id' => null,
            'insurance_provider_id' => $insuranceProvider->id,
            'name' => 'without-quote-type.pdf',
        ]);

        $result = $this->service->getClaimDocuments();

        // Only document with quote_type_id should be included
        $this->assertCount(1, $result['Home']['docs']);
        $this->assertEquals('with-quote-type.pdf', $result['Home']['docs'][0]['docTitle']);
    }

    public function test_get_claim_documents_generates_full_document_url(): void
    {
        $documentType = GenericDocumentType::factory()->create([
            'code' => GenericDocumentTypeCode::CLAIM_FORM->value,
        ]);

        $insuranceProvider = InsuranceProvider::factory()->create();

        GenericDocument::factory()->forHome()->create([
            'documentable_type' => GenericDocumentType::class,
            'documentable_id' => $documentType->id,
            'insurance_provider_id' => $insuranceProvider->id,
            'name' => 'test.pdf',
            'path' => 'documents/claims/test.pdf',
        ]);

        $result = $this->service->getClaimDocuments();

        $docUrl = $result['Home']['docs'][0]['docUrl'];
        $this->assertNotEmpty($docUrl);
        $this->assertStringContainsString('documents/claims/test.pdf', $docUrl);
    }
}

