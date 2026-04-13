<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\OCRSourceEnum;
use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use App\Models\OcrLog;
use App\Models\PassportVisaDetail;
use App\Models\PersonalQuote;
use App\Models\QuoteDocument;
use App\Services\OCR\OcrLogService;
use App\Services\OCR\OCRService;
use App\Services\QuoteDocumentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Helpers\OcrHttpFakeHelper;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\OCRSchema;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    (new OCRSchema)->register();

    config()->set('constants.OCR_API_ENDPOINT', 'https://ocr.example.test');
    config()->set('constants.OCR_API_KEY', 'test-key');
    config()->set('constants.OCR_API_TIMEOUT', 5);
    config()->set('constants.APP_URL', 'https://app.example.test');

    OcrHttpFakeHelper::preventStrayRequests();
});

describe('OCRService Passport (Savings) flow', function () {
    test('happy flow: logs success, persists response_data, and fills passport_visa_details.passport_number', function () {
        $documentTypeCode = 'PP_SAV';
        $ocrDocType = OCRDocumentTypeEnum::PASSPORT;

        TestDataSeeder::seedSavingsDocumentType($documentTypeCode, 'Passport');

        $planCode = 'TEST-PLAN-'.Str::upper(Str::random(8));
        TestDataSeeder::seedApplicationStorage([
            ApplicationStorageEnums::OCR_SAVINGS_PASSPORT_ELIGIBLE_PLAN_CODES => $planCode,
        ]);
        DB::connection('sqlite')->table('insurance_provider_plans')->updateOrInsert(
            ['code' => $planCode],
            [
                'provider_id' => null,
                'sub_type_id' => null,
                'code' => $planCode,
                'text' => $planCode,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $planId = (int) DB::connection('sqlite')->table('insurance_provider_plans')
            ->where('code', $planCode)
            ->value('id');

        $quoteId = DB::connection('sqlite')->table('personal_quotes')->insertGetId([
            'uuid' => 'X85DUBM9',
            'code' => 'SAV-X85DUBM9',
            'quote_type_id' => QuoteTypes::SAVINGS->id(),
            // Keep NOT TransactionApproved to avoid CentralService side-effects in processOcrData().
            'quote_status_id' => 14,
            'plan_id' => $planId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quote = DB::connection('sqlite')->table('personal_quotes')->where('id', $quoteId)->first();
        expect($quote)->not->toBeNull();

        $documentType = DB::connection('sqlite')->table('document_types')->where('code', $documentTypeCode)->first();
        expect($documentType)->not->toBeNull();

        DB::connection('sqlite')->table('quote_documents')->insert([
            'document_type_code' => $documentTypeCode,
            'quote_documentable_id' => $quoteId,
            'quote_documentable_type' => PersonalQuote::class,
            'is_ocr_processed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $docUrl = 'https://azstor.example.test/documents/savings/passport.pdf';

        $quoteDocumentService = Mockery::mock(QuoteDocumentService::class);
        $quoteDocumentService
            ->shouldReceive('getDocumentUrl')
            ->once()
            ->andReturn($docUrl);

        OcrHttpFakeHelper::fakeHealthOk();
        OcrHttpFakeHelper::fakeProcessDocumentOk([
            'passportNumber' => 'V9202312',
            'issuingCountry' => 'ARE',
            'expiryDate' => '2025-01-13T00:00:00Z',
            'fullName' => 'KARUTHEDATH VIGNESH',
            'metadata' => [
                'ref_id' => 'SAV-X85DUBM9',
                'doc_type' => 'PP',
                'api_key_source' => 'ECOM',
            ],
        ]);

        $service = new OCRService($quoteDocumentService, new OcrLogService);

        /** @var PersonalQuote $eloquentQuote */
        $eloquentQuote = PersonalQuote::on('sqlite')->findOrFail($quoteId);
        /** @var DocumentType $eloquentDocType */
        $eloquentDocType = DocumentType::on('sqlite')->where('code', $documentTypeCode)->firstOrFail();

        $result = $service->process(
            QuoteTypes::SAVINGS,
            $eloquentQuote,
            $eloquentDocType,
            'documents/savings/passport.pdf',
            'application/pdf',
            0,
            true,
            false,
            0
        );

        expect($result)->toBeTrue();

        OcrHttpFakeHelper::assertSentProcessDocumentPayload(function (array $payload) use ($docUrl): bool {
            return $payload['doc_type'] === OCRDocumentTypeEnum::PASSPORT->value
                && $payload['quote_type_id'] === QuoteTypes::SAVINGS->id()
                && $payload['uuid'] === 'X85DUBM9'
                && $payload['ref_id'] === 'SAV-X85DUBM9'
                && $payload['doc_url'] === $docUrl
                && $payload['image'] === false;
        });
        OcrHttpFakeHelper::assertSentProcessDocumentSource(OCRSourceEnum::ECOM->value);

        $log = OcrLog::on('sqlite')
            ->where('ocr_loggable_type', PersonalQuote::class)
            ->where('ocr_loggable_id', $quoteId)
            ->where('document_type_code', $documentTypeCode)
            ->latest('id')
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->status)->toBe('success')
            ->and($log->request_data)->toBeArray()
            ->and($log->request_data['doc_type'])->toBe($ocrDocType->value)
            ->and($log->request_data['ref_id'])->toBe('SAV-X85DUBM9')
            ->and($log->response_data)->toBeArray()
            ->and($log->response_data['passportNumber'])->toBe('V9202312');

        $passportVisaDetail = PassportVisaDetail::on('sqlite')
            ->where('quoteable_type', PersonalQuote::class)
            ->where('quoteable_id', $quoteId)
            ->first();

        expect($passportVisaDetail)->not->toBeNull()
            ->and($passportVisaDetail->passport_number)->toBe('V9202312')
            ->and($passportVisaDetail->passport_country)->toBe('ARE')
            ->and($passportVisaDetail->passport_expiry_date)->toBe('2025-01-13');

        $quoteDocument = QuoteDocument::on('sqlite')
            ->where('quote_documentable_id', $quoteId)
            ->where('quote_documentable_type', PersonalQuote::class)
            ->where('document_type_code', $documentTypeCode)
            ->first();

        expect($quoteDocument)->not->toBeNull()
            ->and($quoteDocument->is_ocr_processed)->toBe(1);
    });

    test('unhappy flow: logs failed when OCR API returns no usable data', function () {
        $documentTypeCode = 'PP_SAV';
        TestDataSeeder::seedSavingsDocumentType($documentTypeCode, 'Passport');

        $planCode = 'TEST-PLAN-'.Str::upper(Str::random(8));
        TestDataSeeder::seedApplicationStorage([
            ApplicationStorageEnums::OCR_SAVINGS_PASSPORT_ELIGIBLE_PLAN_CODES => $planCode,
        ]);
        DB::connection('sqlite')->table('insurance_provider_plans')->updateOrInsert(
            ['code' => $planCode],
            [
                'provider_id' => null,
                'sub_type_id' => null,
                'code' => $planCode,
                'text' => $planCode,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $planId = (int) DB::connection('sqlite')->table('insurance_provider_plans')
            ->where('code', $planCode)
            ->value('id');

        $quoteId = DB::connection('sqlite')->table('personal_quotes')->insertGetId([
            'uuid' => 'X85DUBM9',
            'code' => 'SAV-X85DUBM9',
            'quote_type_id' => QuoteTypes::SAVINGS->id(),
            'quote_status_id' => 14,
            'plan_id' => $planId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $docUrl = 'https://azstor.example.test/documents/savings/passport.pdf';

        $quoteDocumentService = Mockery::mock(QuoteDocumentService::class);
        $quoteDocumentService
            ->shouldReceive('getDocumentUrl')
            ->once()
            ->andReturn($docUrl);

        OcrHttpFakeHelper::fakeHealthOk();
        OcrHttpFakeHelper::fakeProcessDocumentFail(500, ['message' => 'temporary']);

        $service = new OCRService($quoteDocumentService, new OcrLogService);

        $eloquentQuote = PersonalQuote::on('sqlite')->findOrFail($quoteId);
        $eloquentDocType = DocumentType::on('sqlite')->where('code', $documentTypeCode)->firstOrFail();

        $result = $service->process(
            QuoteTypes::SAVINGS,
            $eloquentQuote,
            $eloquentDocType,
            'documents/savings/passport.pdf',
            'application/pdf',
            0,
            true,
            false,
            0
        );

        expect($result)->toBeFalse();

        $log = OcrLog::on('sqlite')
            ->where('ocr_loggable_type', PersonalQuote::class)
            ->where('ocr_loggable_id', $quoteId)
            ->where('document_type_code', $documentTypeCode)
            ->latest('id')
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->status)->toBe('failed');
    });

    test('sets quote_documents.is_ocr_processed to false when OCR omits a required passport field', function () {
        $documentTypeCode = 'PP_SAV';

        TestDataSeeder::seedSavingsDocumentType($documentTypeCode, 'Passport');

        $planCode = 'TEST-PLAN-'.Str::upper(Str::random(8));
        TestDataSeeder::seedApplicationStorage([
            ApplicationStorageEnums::OCR_SAVINGS_PASSPORT_ELIGIBLE_PLAN_CODES => $planCode,
        ]);
        DB::connection('sqlite')->table('insurance_provider_plans')->updateOrInsert(
            ['code' => $planCode],
            [
                'provider_id' => null,
                'sub_type_id' => null,
                'code' => $planCode,
                'text' => $planCode,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $planId = (int) DB::connection('sqlite')->table('insurance_provider_plans')
            ->where('code', $planCode)
            ->value('id');

        $quoteId = DB::connection('sqlite')->table('personal_quotes')->insertGetId([
            'uuid' => 'X85DUBPART',
            'code' => 'SAV-X85DUBPART',
            'quote_type_id' => QuoteTypes::SAVINGS->id(),
            'quote_status_id' => 14,
            'plan_id' => $planId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('sqlite')->table('quote_documents')->insert([
            'document_type_code' => $documentTypeCode,
            'quote_documentable_id' => $quoteId,
            'quote_documentable_type' => PersonalQuote::class,
            'is_ocr_processed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $docUrl = 'https://azstor.example.test/documents/savings/passport-partial.pdf';

        $quoteDocumentService = Mockery::mock(QuoteDocumentService::class);
        $quoteDocumentService
            ->shouldReceive('getDocumentUrl')
            ->once()
            ->andReturn($docUrl);

        OcrHttpFakeHelper::fakeHealthOk();
        // Missing issuingCountry — passport_country stays empty; validator must mark OCR as incomplete.
        OcrHttpFakeHelper::fakeProcessDocumentOk([
            'passportNumber' => 'V9202312',
            'expiryDate' => '2026-06-01T00:00:00Z',
            'fullName' => 'PARTIAL USER',
            'metadata' => [
                'ref_id' => 'SAV-X85DUBPART',
                'doc_type' => 'PP',
                'api_key_source' => 'ECOM',
            ],
        ]);

        $service = new OCRService($quoteDocumentService, new OcrLogService);

        $eloquentQuote = PersonalQuote::on('sqlite')->findOrFail($quoteId);
        $eloquentDocType = DocumentType::on('sqlite')->where('code', $documentTypeCode)->firstOrFail();

        $result = $service->process(
            QuoteTypes::SAVINGS,
            $eloquentQuote,
            $eloquentDocType,
            'documents/savings/passport-partial.pdf',
            'application/pdf',
            0,
            true,
            false,
            0
        );

        expect($result)->toBeTrue();

        $quoteDocument = QuoteDocument::on('sqlite')
            ->where('quote_documentable_id', $quoteId)
            ->where('quote_documentable_type', PersonalQuote::class)
            ->where('document_type_code', $documentTypeCode)
            ->first();

        expect($quoteDocument)->not->toBeNull()
            ->and($quoteDocument->is_ocr_processed)->toBe(0);
    });
});
