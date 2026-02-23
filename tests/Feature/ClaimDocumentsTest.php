<?php

use App\Enums\GenericDocumentTypeCode;
use App\Enums\InsuranceProviderEnum;
use App\Enums\QuoteTypeId;
use App\Models\BusinessTypeOfInsurance;
use App\Models\Claim;
use App\Models\GenericDocument;
use App\Models\GenericDocumentType;
use App\Models\InsuranceProvider;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('get claim documents returns documents for Home quote type', function () {
    // Create GenericDocumentType with CLAIM_FORM code
    $documentType = GenericDocumentType::factory()->create([
        'code' => GenericDocumentTypeCode::CLAIM_FORM->value,
        'text' => 'Claim form',
        'description' => 'download and upload your signed and completed claim form.',
    ]);

    // Create Insurance Providers
    $insuranceProvider1 = InsuranceProvider::factory()->create([
        'code' => InsuranceProviderEnum::RSA->value,
        'text' => InsuranceProviderEnum::getTextByCode(InsuranceProviderEnum::RSA->value),
    ]);
    $insuranceProvider2 = InsuranceProvider::factory()->create([
        'code' => InsuranceProviderEnum::AXA->value,
        'text' => InsuranceProviderEnum::getTextByCode(InsuranceProviderEnum::AXA->value),
    ]);

    // Create documents for Home (quote_type_id = 2); API filters by documentable_type = Claim::class
    GenericDocument::factory()->forHome()->create([
        'documentable_type' => Claim::class,
        'documentable_id' => 1,
        'name' => 'QIC.pdf',
        'path' => 'documents/claims/QIC.pdf',
        'insurance_provider_id' => $insuranceProvider1->id,
    ]);

    GenericDocument::factory()->forHome()->create([
        'documentable_type' => Claim::class,
        'documentable_id' => 1,
        'name' => 'LIVA.pdf',
        'path' => 'documents/claims/LIVA.pdf',
        'insurance_provider_id' => $insuranceProvider2->id,
        'business_type_of_insurance_id' => null, // Some documents may not have business type
    ]);

    // Make API request
    $response = $this->getJson('/api/v1/claim-documents');

    // Assert response structure
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'Home' => [
                    'quoteTypeId',
                    'docs' => [
                        '*' => [
                            'insuranceProviderId',
                            'insuranceProviderCode',
                            'docUrl',
                            'docTitle',
                        ],
                    ],
                ],
            ],
        ]);

    $data = $response->json('data');

    // Assert Home has documents
    expect($data['Home']['quoteTypeId'])->toBe(QuoteTypeId::Home)
        ->and($data['Home']['docs'])->toHaveCount(2);

    // Assert first document (Home should NOT have business_type_of_insurance fields)
    $firstDoc = $data['Home']['docs'][0];
    expect($firstDoc['insuranceProviderId'])->toBe($insuranceProvider1->id)
        ->and($firstDoc['insuranceProviderCode'])->toBe(InsuranceProviderEnum::RSA->value)
        ->and($firstDoc)->not->toHaveKey('businessTypeOfInsuranceId')
        ->and($firstDoc)->not->toHaveKey('businessTypeOfInsurance')
        ->and($firstDoc['docTitle'])->toBe('QIC.pdf')
        ->and($firstDoc['docUrl'])->toContain('documents/claims/QIC.pdf');
});

test('get claim documents returns empty docs for Travel quote type when no documents exist', function () {
    // Create GenericDocumentType with CLAIM_FORM code
    $documentType = GenericDocumentType::factory()->create([
        'code' => GenericDocumentTypeCode::CLAIM_FORM->value,
        'text' => 'Claim form',
    ]);

    // Create documents only for Home (quote_type_id = 2), not for Travel (quote_type_id = 8)
    $insuranceProvider = InsuranceProvider::factory()->create();

    GenericDocument::factory()->forHome()->create([
        'documentable_type' => Claim::class,
        'documentable_id' => 1,
        'insurance_provider_id' => $insuranceProvider->id,
    ]);

    // Make API request
    $response = $this->getJson('/api/v1/claim-documents');

    $response->assertStatus(200);
    $data = $response->json('data');

    // Assert Travel has empty docs array
    expect($data['Travel']['quoteTypeId'])->toBe(QuoteTypeId::Travel)
        ->and($data['Travel']['docs'])->toBeArray()
        ->and($data['Travel']['docs'])->toHaveCount(0);

    // Assert Home has documents
    expect($data['Home']['docs'])->toHaveCount(1);
});

test('get claim documents returns empty structure when no documents exist', function () {
    // Create GenericDocumentType but no documents
    GenericDocumentType::factory()->create([
        'code' => GenericDocumentTypeCode::CLAIM_FORM->value,
        'text' => 'Claim form',
    ]);

    // Make API request
    $response = $this->getJson('/api/v1/claim-documents');

    $response->assertStatus(200);
    $data = $response->json('data');

    // Assert all quote types are present with empty docs
    expect($data)->toHaveKey('Home')
        ->and($data)->toHaveKey('Travel')
        ->and($data['Home']['docs'])->toBeArray()
        ->and($data['Home']['docs'])->toHaveCount(0)
        ->and($data['Travel']['docs'])->toBeArray()
        ->and($data['Travel']['docs'])->toHaveCount(0);
});

test('get claim documents includes business type of insurance only for Business LOB', function () {
    // Create GenericDocumentType
    $documentType = GenericDocumentType::factory()->create([
        'code' => GenericDocumentTypeCode::CLAIM_FORM->value,
    ]);

    $insuranceProvider = InsuranceProvider::factory()->create();
    $businessType = BusinessTypeOfInsurance::factory()->create(['text' => 'Property']);

    // Document for Business LOB with business type; API filters by documentable_type = Claim::class
    GenericDocument::factory()->create([
        'documentable_type' => Claim::class,
        'documentable_id' => 1,
        'quote_type_id' => QuoteTypeId::Business,
        'insurance_provider_id' => $insuranceProvider->id,
        'business_type_of_insurance_id' => $businessType->id,
        'name' => 'BusinessWithType.pdf',
    ]);

    // Document for Business LOB without business type
    GenericDocument::factory()->create([
        'documentable_type' => Claim::class,
        'documentable_id' => 1,
        'quote_type_id' => QuoteTypeId::Business,
        'insurance_provider_id' => $insuranceProvider->id,
        'business_type_of_insurance_id' => null,
        'name' => 'BusinessWithoutType.pdf',
    ]);

    // Document for Home LOB (should NOT have business_type_of_insurance even if set)
    GenericDocument::factory()->forHome()->create([
        'documentable_type' => Claim::class,
        'documentable_id' => 1,
        'insurance_provider_id' => $insuranceProvider->id,
        'business_type_of_insurance_id' => $businessType->id,
        'name' => 'HomeWithType.pdf',
    ]);

    $response = $this->getJson('/api/v1/claim-documents');

    $response->assertStatus(200);

    // Check Business LOB documents
    $businessDocs = $response->json('data.Business.docs');
    $withBusinessType = collect($businessDocs)->firstWhere('docTitle', 'BusinessWithType.pdf');
    $withoutBusinessType = collect($businessDocs)->firstWhere('docTitle', 'BusinessWithoutType.pdf');

    expect($withBusinessType)->toHaveKey('businessTypeOfInsuranceId')
        ->and($withBusinessType['businessTypeOfInsuranceId'])->toBe($businessType->id)
        ->and($withBusinessType)->toHaveKey('businessTypeOfInsurance')
        ->and($withBusinessType['businessTypeOfInsurance'])->not->toBeNull()
        ->and($withBusinessType['businessTypeOfInsurance']['text'])->toBe('Property')
        ->and($withoutBusinessType)->not->toHaveKey('businessTypeOfInsuranceId')
        ->and($withoutBusinessType)->not->toHaveKey('businessTypeOfInsurance');

    // Check Home LOB document (should NOT have business_type_of_insurance fields)
    $homeDocs = $response->json('data.Home.docs');
    $homeDoc = collect($homeDocs)->firstWhere('docTitle', 'HomeWithType.pdf');

    expect($homeDoc)->not->toHaveKey('businessTypeOfInsuranceId')
        ->and($homeDoc)->not->toHaveKey('businessTypeOfInsurance');
});
