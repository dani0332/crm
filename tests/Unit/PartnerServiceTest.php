<?php

use App\Enums\DocumentTypeCode;
use App\Enums\InsuranceProviderEnum;
use App\Models\CarQuote;
use App\Models\Partner;
use App\Services\PartnerService;
use App\Services\QuoteDocumentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

use function Pest\Laravel\mock;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $adminUser = TestDataSeeder::createAdminUser([
        'email' => 'admin@example.com',
    ]);
    Auth::guard('web')->login($adminUser);

    // Create partners and partner_plans tables
    Schema::connection('sqlite')->create('partners', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique();
        $table->string('email')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::connection('sqlite')->create('partner_plans', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('partner_id');
        $table->unsignedBigInteger('provider_id');
        $table->timestamps();
    });

    Schema::connection('sqlite')->create('vehicle_type', function (Blueprint $table) {
        $table->id();
        $table->string('text')->nullable();
        $table->string('text_ar')->nullable();
        $table->boolean('is_active')->default(1);
        $table->timestamps();
        $table->softDeletes();
    });

    // Create test partner and insurance provider data
    $this->insuranceProviderId = DB::connection('sqlite')->table('insurance_provider')->insertGetId([
        'code' => InsuranceProviderEnum::AXA->value,
        'text' => 'AXA Insurance',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->partnerId = DB::connection('sqlite')->table('partners')->insertGetId([
        'code' => 'TEST_PARTNER',
        'email' => 'partner@example.com',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::connection('sqlite')->table('partner_plans')->insert([
        'partner_id' => $this->partnerId,
        'provider_id' => $this->insuranceProviderId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Set up application storage for Bird service
    TestDataSeeder::seedApplicationStorage([
        \App\Enums\ApplicationStorageEnums::BIRD_PARTNER_AUTOMATION_COMPLETED_WORKFLOW_URL => 'https://example.test/bird/workflow',
    ]);

    // Mock QuoteDocumentService and bind to container
    $this->quoteDocumentServiceMock = mock(QuoteDocumentService::class);
    $this->app->instance(QuoteDocumentService::class, $this->quoteDocumentServiceMock);
});

afterEach(function () {
    Mockery::close();
});

describe('isPartnerActive', function () {
    it('returns active partner when conditions are met', function () {
        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->insuranceProviderId);

        expect($result)->not->toBeFalse()
            ->and($result->code)->toBe('TEST_PARTNER')
            ->and($result->email)->toBe('partner@example.com')
            ->and($result->is_active)->toBe(1);
    });

    it('returns false when partner is inactive', function () {
        DB::connection('sqlite')->table('partners')
            ->where('id', $this->partnerId)
            ->update(['is_active' => false]);

        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->insuranceProviderId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner has no email', function () {
        DB::connection('sqlite')->table('partners')
            ->where('id', $this->partnerId)
            ->update(['email' => null]);

        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->insuranceProviderId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner does not exist', function () {
        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('NON_EXISTENT', $this->insuranceProviderId);

        expect($result)->toBeFalse();
    });

    it('returns partner but with empty partner plans when partner has no plan for the insurance provider', function () {
        $differentProviderId = DB::connection('sqlite')->table('insurance_provider')->insertGetId([
            'code' => 'RSA',
            'text' => 'RSA Insurance',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $differentProviderId);

        expect($result)->not->toBeFalse()
            ->and($result->partnerPlans)->toBeEmpty();
    });
});

describe('validatePartnerQuote', function () {
    it('returns quote when all validations pass', function () {
        $lookups = TestDataSeeder::seedCarQuoteLookups();

        $carQuote = TestDataSeeder::createCarQuote([
            'source' => 'TEST_PARTNER',
            'insurance_provider_id' => $this->insuranceProviderId,
            'plan_id' => $lookups['plan_id'],
        ]);

        DB::connection('sqlite')->table('payments')->insert([
            'code' => $carQuote->code,
            'plan_id' => $lookups['plan_id'],
            'insurance_provider_id' => $this->insuranceProviderId,
            'paymentable_id' => $carQuote->id,
            'paymentable_type' => CarQuote::class,
            'total_price' => 1000,
            'total_amount' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(PartnerService::class);

        $result = $service->validatePartnerQuote($carQuote->uuid, 'car');

        expect($result)->toBeInstanceOf(CarQuote::class)
            ->and($result->uuid)->toBe($carQuote->uuid);
    });

    it('returns false when quote does not exist', function () {
        $service = app(PartnerService::class);

        $result = $service->validatePartnerQuote('non-existent-uuid', 'car');

        expect($result)->toBeFalse();
    });

    it('returns false when payment does not exist', function () {
        $carQuote = TestDataSeeder::createCarQuote([
            'source' => 'TEST_PARTNER',
        ]);

        $service = app(PartnerService::class);

        $result = $service->validatePartnerQuote($carQuote->uuid, 'car');

        expect($result)->toBeFalse();
    });

    it('returns false when partner is not active', function () {
        $lookups = TestDataSeeder::seedCarQuoteLookups();

        $carQuote = TestDataSeeder::createCarQuote([
            'source' => 'INACTIVE_PARTNER',
            'insurance_provider_id' => $this->insuranceProviderId,
            'plan_id' => $lookups['plan_id'],
        ]);

        DB::connection('sqlite')->table('payments')->insert([
            'code' => $carQuote->code,
            'plan_id' => $lookups['plan_id'],
            'insurance_provider_id' => $this->insuranceProviderId,
            'paymentable_id' => $carQuote->id,
            'paymentable_type' => CarQuote::class,
            'total_price' => 1000,
            'total_amount' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(PartnerService::class);

        $result = $service->validatePartnerQuote($carQuote->uuid, 'car');

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
