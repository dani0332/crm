<?php

use App\Enums\DocumentTypeCode;
use App\Enums\InsuranceProviderEnum;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Services\PartnerService;
use App\Services\QuoteDocumentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

use function Pest\Laravel\mock;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    (new Tests\Support\Schema\PartnerSchema)->register();
    DB::setDefaultConnection('sqlite');

    $adminUser = TestDataSeeder::createAdminUser([
        'email' => 'admin@example.com',
    ]);
    Auth::guard('web')->login($adminUser);

    $this->insuranceProviderId = DB::table('insurance_provider')->insertGetId([
        'code' => InsuranceProviderEnum::AXA->value,
        'text' => 'AXA Insurance',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->quoteTypeId = QuoteTypes::CAR->id();

    $this->partnerId = DB::table('insurance_partners')->insertGetId([
        'code' => 'TEST_PARTNER',
        'email' => 'partner@example.com',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->partnerProviderId = DB::table('insurance_partner_providers')->insertGetId([
        'partner_id' => $this->partnerId,
        'provider_id' => $this->insuranceProviderId,
        'quote_type_id' => $this->quoteTypeId,
        'auto_issuance_enabled' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->planId = 1;

    DB::table('insurance_partner_provider_plans')->insert([
        'partner_provider_id' => $this->partnerProviderId,
        'plan_id' => $this->planId,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    TestDataSeeder::seedApplicationStorage([
        \App\Enums\ApplicationStorageEnums::BIRD_PARTNER_AUTOMATION_COMPLETED_WORKFLOW_URL => 'https://example.test/bird/workflow',
        \App\Enums\ApplicationStorageEnums::AXA_POLICY_MANDATORY_DOCUMENTS => json_encode([DocumentTypeCode::TI, DocumentTypeCode::CTIRBB, DocumentTypeCode::CPC, DocumentTypeCode::CPS]),
    ]);

    $this->quoteDocumentServiceMock = mock(QuoteDocumentService::class);
    $this->app->instance(QuoteDocumentService::class, $this->quoteDocumentServiceMock);
});

afterEach(function () {
    Mockery::close();
});

describe('isPartnerActive', function () {
    it('returns active partner when conditions are met', function () {
        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProviderId, $this->planId);

        expect($result)->not->toBeFalse()
            ->and($result->code)->toBe('TEST_PARTNER')
            ->and($result->email)->toBe('partner@example.com')
            ->and($result->is_active)->toBe(1);
    });

    it('returns false when partner is inactive', function () {
        DB::table('insurance_partners')
            ->where('id', $this->partnerId)
            ->update(['is_active' => false]);

        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProviderId, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner has no email', function () {
        DB::table('insurance_partners')
            ->where('id', $this->partnerId)
            ->update(['email' => null]);

        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProviderId, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner has an empty string email', function () {
        DB::table('insurance_partners')
            ->where('id', $this->partnerId)
            ->update(['email' => '']);

        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProviderId, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner does not exist', function () {
        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('NON_EXISTENT', $this->quoteTypeId, $this->insuranceProviderId, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner has no provider for the given insurance provider', function () {
        $differentProviderId = DB::table('insurance_provider')->insertGetId([
            'code' => 'RSA',
            'text' => 'RSA Insurance',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $differentProviderId, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner provider has auto_issuance_enabled set to false', function () {
        DB::table('insurance_partner_providers')
            ->where('id', $this->partnerProviderId)
            ->update(['auto_issuance_enabled' => false]);

        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProviderId, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner provider is inactive', function () {
        DB::table('insurance_partner_providers')
            ->where('id', $this->partnerProviderId)
            ->update(['is_active' => false]);

        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProviderId, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when quote_type_id does not match', function () {
        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', QuoteTypes::HEALTH->id(), $this->insuranceProviderId, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when plan is inactive', function () {
        DB::table('insurance_partner_provider_plans')
            ->where('partner_provider_id', $this->partnerProviderId)
            ->update(['is_active' => false]);

        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProviderId, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when plan_id does not match', function () {
        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProviderId, 999);

        expect($result)->toBeFalse();
    });

    it('returns false when plan_id is null', function () {
        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProviderId, null);

        expect($result)->toBeFalse();
    });
});

describe('validatePartnerQuote', function () {
    it('returns quote when all validations pass', function () {
        $lookups = TestDataSeeder::seedCarQuoteLookups();

        $rsaPartnerProviderId = DB::table('insurance_partner_providers')->insertGetId([
            'partner_id' => $this->partnerId,
            'provider_id' => $lookups['insurance_provider_id'],
            'quote_type_id' => $this->quoteTypeId,
            'auto_issuance_enabled' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('insurance_partner_provider_plans')->insert([
            'partner_provider_id' => $rsaPartnerProviderId,
            'plan_id' => $lookups['plan_id'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $carQuote = TestDataSeeder::createCarQuote([
            'source' => 'TEST_PARTNER',
            'insurance_provider_id' => $this->insuranceProviderId,
            'plan_id' => $lookups['plan_id'],
        ]);

        DB::table('payments')->insert([
            'code' => $carQuote->code,
            'plan_id' => $lookups['plan_id'],
            'insurance_provider_id' => $lookups['insurance_provider_id'],
            'paymentable_id' => $carQuote->id,
            'paymentable_type' => CarQuote::class,
            'total_price' => 1000,
            'total_amount' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(PartnerService::class);

        $result = $service->validatePartnerQuote($carQuote->uuid, QuoteTypes::CAR);

        expect($result)->toBeArray()
            ->and($result['quote'])->toBeInstanceOf(CarQuote::class)
            ->and($result['quote']->uuid)->toBe($carQuote->uuid)
            ->and($result)->toHaveKeys(['quote', 'insuranceProvider', 'partnerEmail']);
    });

    it('returns false when quote does not exist', function () {
        $service = app(PartnerService::class);

        $result = $service->validatePartnerQuote('non-existent-uuid', QuoteTypes::CAR);

        expect($result)->toBeFalse();
    });

    it('returns false when payment does not exist', function () {
        $carQuote = TestDataSeeder::createCarQuote([
            'source' => 'TEST_PARTNER',
        ]);

        $service = app(PartnerService::class);

        $result = $service->validatePartnerQuote($carQuote->uuid, QuoteTypes::CAR);

        expect($result)->toBeFalse();
    });

    it('returns false when partner is not active', function () {
        $lookups = TestDataSeeder::seedCarQuoteLookups();

        $carQuote = TestDataSeeder::createCarQuote([
            'source' => 'INACTIVE_PARTNER',
            'insurance_provider_id' => $this->insuranceProviderId,
            'plan_id' => $lookups['plan_id'],
        ]);

        DB::table('payments')->insert([
            'code' => $carQuote->code,
            'plan_id' => $lookups['plan_id'],
            'insurance_provider_id' => $lookups['insurance_provider_id'],
            'paymentable_id' => $carQuote->id,
            'paymentable_type' => CarQuote::class,
            'total_price' => 1000,
            'total_amount' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(PartnerService::class);

        $result = $service->validatePartnerQuote($carQuote->uuid, QuoteTypes::CAR);

        expect($result)->toBeFalse();
    });
});

describe('partnerPolicyDocumentsEmailPayload', function () {
    it('generates correct email payload with document URLs', function () {
        $this->quoteDocumentServiceMock
            ->shouldReceive('getDocumentUrl')
            ->with('https://example.com/doc1.pdf', 'azureIMPrivate')
            ->once()
            ->andReturn('https://azure.com/doc1.pdf');

        $this->quoteDocumentServiceMock
            ->shouldReceive('getDocumentExtension')
            ->with('https://example.com/doc1.pdf')
            ->once()
            ->andReturn('pdf');

        $documents = [
            [
                'document_type_code' => DocumentTypeCode::TI,
                'doc_url' => 'https://example.com/doc1.pdf',
            ],
        ];

        $service = app(PartnerService::class);

        $result = $service->partnerPolicyDocumentsEmailPayload($documents);

        expect($result)->toBeArray()
            ->and($result[DocumentTypeCode::TI])->toBe('https://azure.com/doc1.pdf')
            ->and($result['EXT_'.DocumentTypeCode::TI])->toBe('pdf');
    });

    it('prefers watermarked document URL over regular doc URL', function () {
        $this->quoteDocumentServiceMock
            ->shouldReceive('getDocumentUrl')
            ->with('https://example.com/watermarked.pdf', 'azureIMPrivate')
            ->once()
            ->andReturn('https://azure.com/watermarked.pdf');

        $this->quoteDocumentServiceMock
            ->shouldReceive('getDocumentExtension')
            ->with('https://example.com/watermarked.pdf')
            ->once()
            ->andReturn('pdf');

        $documents = [
            [
                'document_type_code' => DocumentTypeCode::TI,
                'doc_url' => 'https://example.com/doc1.pdf',
                'watermarked_doc_url' => 'https://example.com/watermarked.pdf',
            ],
        ];

        $service = app(PartnerService::class);

        $result = $service->partnerPolicyDocumentsEmailPayload($documents);

        expect($result)->toBeArray()
            ->and($result[DocumentTypeCode::TI])->toBe('https://azure.com/watermarked.pdf');
    });

    it('handles multiple documents correctly', function () {
        $this->quoteDocumentServiceMock
            ->shouldReceive('getDocumentUrl')
            ->times(2)
            ->andReturn('https://azure.com/document.pdf');

        $this->quoteDocumentServiceMock
            ->shouldReceive('getDocumentExtension')
            ->times(2)
            ->andReturn('pdf');

        $documents = [
            [
                'document_type_code' => DocumentTypeCode::TI,
                'doc_url' => 'https://example.com/doc1.pdf',
            ],
            [
                'document_type_code' => DocumentTypeCode::CTIRBB,
                'doc_url' => 'https://example.com/doc2.pdf',
            ],
        ];

        $service = app(PartnerService::class);

        $result = $service->partnerPolicyDocumentsEmailPayload($documents);

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys([
                DocumentTypeCode::TI,
                'EXT_'.DocumentTypeCode::TI,
                DocumentTypeCode::CTIRBB,
                'EXT_'.DocumentTypeCode::CTIRBB,
            ]);
    });

    it('returns empty string when document URL is null', function () {
        $this->quoteDocumentServiceMock
            ->shouldReceive('getDocumentUrl')
            ->once()
            ->andReturn(null);

        $this->quoteDocumentServiceMock
            ->shouldReceive('getDocumentExtension')
            ->once()
            ->andReturn(null);

        $documents = [
            [
                'document_type_code' => DocumentTypeCode::TI,
                'doc_url' => 'https://example.com/doc1.pdf',
            ],
        ];

        $service = app(PartnerService::class);

        $result = $service->partnerPolicyDocumentsEmailPayload($documents);

        expect($result)->toBeArray()
            ->and($result[DocumentTypeCode::TI])->toBe('')
            ->and($result['EXT_'.DocumentTypeCode::TI])->toBe('');
    });
});

describe('getProviderPolicyDocuments', function () {
    it('calls QuoteDocumentService with correct parameters', function () {
        $quote = (object) ['id' => 123];
        $quoteType = 'car';
        $providerDocuments = [DocumentTypeCode::TI, DocumentTypeCode::CTIRBB];

        $this->quoteDocumentServiceMock
            ->shouldReceive('getQuoteDocuments')
            ->with($quoteType, 123, $providerDocuments)
            ->once()
            ->andReturn([]);

        $service = app(PartnerService::class);

        $result = $service->getProviderPolicyDocuments($quote, $quoteType, $providerDocuments);

        expect($result)->toBeArray();
    });
});
