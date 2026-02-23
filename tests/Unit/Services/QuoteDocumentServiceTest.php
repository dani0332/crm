<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\GenericDocumentTypeCode;
use App\Enums\InsuranceProviderEnum;
use App\Enums\QuoteTypeId;
use App\Models\BusinessTypeOfInsurance;
use App\Models\Claim;
use App\Models\GenericDocument;
use App\Models\GenericDocumentType;
use App\Models\InsuranceProvider;
use App\Services\QuoteDocumentService;
use Illuminate\Database\Eloquent\Collection;
use Tests\Helpers\TestSchemaCreator;
use Tests\TestCase;

class QuoteDocumentServiceTest extends TestCase
{
    protected QuoteDocumentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        TestSchemaCreator::createMinimalSchema();
        $this->service = new QuoteDocumentService;
    }

    public function test_get_empty_claim_documents_response_structure(): void
    {
        $result = $this->service->getEmptyClaimDocumentsResponseStructure();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Check that all expected quote types are present
        $expectedQuoteTypes = QuoteTypeId::getClaimDocumentQuoteTypes();
        foreach ($expectedQuoteTypes as $quoteTypeId) {
            $lob = QuoteTypeId::getDisplayName($quoteTypeId);
            if ($lob) {
                $this->assertArrayHasKey($lob, $result);
                $this->assertEquals($quoteTypeId, $result[$lob]['quoteTypeId']);
                $this->assertIsArray($result[$lob]['docs']);
                $this->assertEmpty($result[$lob]['docs']);
            }
        }

        // Verify structure for specific LOBs
        $this->assertArrayHasKey('Home', $result);
        $this->assertEquals(QuoteTypeId::Home, $result['Home']['quoteTypeId']);
        $this->assertIsArray($result['Home']['docs']);
        $this->assertEmpty($result['Home']['docs']);

        $this->assertArrayHasKey('Business', $result);
        $this->assertEquals(QuoteTypeId::Business, $result['Business']['quoteTypeId']);
        $this->assertIsArray($result['Business']['docs']);
        $this->assertEmpty($result['Business']['docs']);

        $this->assertArrayHasKey('Travel', $result);
        $this->assertEquals(QuoteTypeId::Travel, $result['Travel']['quoteTypeId']);
        $this->assertIsArray($result['Travel']['docs']);
        $this->assertEmpty($result['Travel']['docs']);
    }

    public function test_group_claim_documents_by_quote_type(): void
    {
        $insuranceProvider = InsuranceProvider::factory()->create([
            'code' => InsuranceProviderEnum::RSA->value,
        ]);
        $businessType = BusinessTypeOfInsurance::factory()->create(['text' => 'Property', 'code' => 'PROP']);

        // Create documents for different scenarios
        $homeDocument = GenericDocument::factory()->make([
            'quote_type_id' => QuoteTypeId::Home,
            'insurance_provider_id' => $insuranceProvider->id,
            'name' => 'home-doc.pdf',
            'path' => 'documents/claims/home-doc.pdf',
        ]);
        $homeDocument->insuranceProvider = $insuranceProvider;

        $travelDocument = GenericDocument::factory()->make([
            'quote_type_id' => QuoteTypeId::Travel,
            'insurance_provider_id' => $insuranceProvider->id,
            'name' => 'travel-doc.pdf',
            'path' => 'documents/claims/travel-doc.pdf',
        ]);
        $travelDocument->insuranceProvider = $insuranceProvider;

        $businessDocument = GenericDocument::factory()->make([
            'quote_type_id' => QuoteTypeId::Business,
            'insurance_provider_id' => $insuranceProvider->id,
            'business_type_of_insurance_id' => $businessType->id,
            'name' => 'business-doc.pdf',
            'path' => 'documents/claims/business-doc.pdf',
        ]);
        $businessDocument->insuranceProvider = $insuranceProvider;
        $businessDocument->businessTypeOfInsurance = $businessType;

        // Document without insurance provider (should be skipped)
        $documentWithoutProvider = GenericDocument::factory()->make([
            'quote_type_id' => QuoteTypeId::Home,
            'insurance_provider_id' => null,
            'name' => 'no-provider.pdf',
        ]);
        $documentWithoutProvider->insuranceProvider = null;

        // Document without quote_type_id (should be skipped)
        $documentWithoutQuoteType = GenericDocument::factory()->make([
            'quote_type_id' => null,
            'insurance_provider_id' => $insuranceProvider->id,
            'name' => 'no-quote-type.pdf',
        ]);
        $documentWithoutQuoteType->insuranceProvider = $insuranceProvider;

        $documents = new Collection([
            $homeDocument,
            $travelDocument,
            $businessDocument,
            $documentWithoutProvider,
            $documentWithoutQuoteType,
        ]);

        $result = $this->service->groupClaimDocumentsByQuoteType($documents);

        // Verify grouping works correctly
        $this->assertIsArray($result);
        $this->assertCount(1, $result['Home']['docs']);
        $this->assertCount(1, $result['Travel']['docs']);
        $this->assertCount(1, $result['Business']['docs']);

        // Verify Home document structure
        $homeDoc = $result['Home']['docs'][0];
        $this->assertEquals($insuranceProvider->id, $homeDoc['insuranceProviderId']);
        $this->assertEquals(InsuranceProviderEnum::RSA->value, $homeDoc['insuranceProviderCode']);
        $this->assertEquals('home-doc.pdf', $homeDoc['docTitle']);
        $this->assertStringContainsString('documents/claims/home-doc.pdf', $homeDoc['docUrl']);
        $this->assertArrayNotHasKey('businessTypeOfInsuranceId', $homeDoc);
        $this->assertArrayNotHasKey('businessTypeOfInsurance', $homeDoc);

        // Verify Travel document structure
        $travelDoc = $result['Travel']['docs'][0];
        $this->assertEquals($insuranceProvider->id, $travelDoc['insuranceProviderId']);
        $this->assertEquals('travel-doc.pdf', $travelDoc['docTitle']);
        $this->assertStringContainsString('documents/claims/travel-doc.pdf', $travelDoc['docUrl']);

        // Verify Business document includes business_type_of_insurance
        $businessDoc = $result['Business']['docs'][0];
        $this->assertArrayHasKey('businessTypeOfInsuranceId', $businessDoc);
        $this->assertArrayHasKey('businessTypeOfInsurance', $businessDoc);
        $this->assertEquals($businessType->id, $businessDoc['businessTypeOfInsuranceId']);
        $this->assertEquals('Property', $businessDoc['businessTypeOfInsurance']['text']);
        $this->assertEquals('PROP', $businessDoc['businessTypeOfInsurance']['code']);

        // Verify documents without provider or quote_type_id are skipped
        $this->assertCount(1, $result['Home']['docs'], 'Documents without provider or quote_type_id should be skipped');
    }

    public function test_get_claim_documents(): void
    {
        // Test with no document type
        $result = $this->service->getClaimDocuments();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('Home', $result);
        $this->assertArrayHasKey('Travel', $result);
        $this->assertArrayHasKey('Business', $result);
        $this->assertEmpty($result['Home']['docs']);
        $this->assertEmpty($result['Travel']['docs']);
        $this->assertEmpty($result['Business']['docs']);

        // Test with document type but no documents
        $documentType = GenericDocumentType::factory()->create([
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

        // Test with documents (reuse the same documentType created above)
        $insuranceProvider = InsuranceProvider::factory()->create([
            'code' => InsuranceProviderEnum::RSA->value,
        ]);

        // Service filters by documentable_type = Claim::class
        GenericDocument::factory()->forHome()->create([
            'documentable_type' => Claim::class,
            'documentable_id' => 1,
            'insurance_provider_id' => $insuranceProvider->id,
            'name' => 'home-doc.pdf',
        ]);

        GenericDocument::factory()->forTravel()->create([
            'documentable_type' => Claim::class,
            'documentable_id' => 1,
            'insurance_provider_id' => $insuranceProvider->id,
            'name' => 'travel-doc.pdf',
        ]);

        $result = $this->service->getClaimDocuments();

        $this->assertIsArray($result);
        $this->assertCount(1, $result['Home']['docs']);
        $this->assertCount(1, $result['Travel']['docs']);
        $this->assertEquals(QuoteTypeId::Home, $result['Home']['quoteTypeId']);
        $this->assertEquals(QuoteTypeId::Travel, $result['Travel']['quoteTypeId']);
        $this->assertEquals('home-doc.pdf', $result['Home']['docs'][0]['docTitle']);
        $this->assertEquals('travel-doc.pdf', $result['Travel']['docs'][0]['docTitle']);
    }
}
