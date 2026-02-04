<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use App\Models\OcrLog;
use App\Models\PersonalQuote;
use App\Services\OCR\OcrLogService;
use App\Services\OCR\OCRService;
use App\Services\QuoteDocumentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\OcrHttpFakeHelper;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createOCRSchema();

    config()->set('constants.OCR_API_ENDPOINT', 'https://ocr.example.test');
    config()->set('constants.OCR_API_KEY', 'test-key');
    config()->set('constants.OCR_API_TIMEOUT', 5);
    config()->set('constants.APP_URL', 'https://app.example.test');

    OcrHttpFakeHelper::preventStrayRequests();
});

describe('OCRService validations / gates', function () {
    test('service unavailable: returns false and logs failed', function () {
        TestDataSeeder::seedSavingsDocumentType('SAV_PP', 'Passport');

        $quoteId = DB::connection('sqlite')->table('personal_quotes')->insertGetId([
            'uuid' => 'X85DUBM9',
            'code' => 'SAV-X85DUBM9',
            'quote_type_id' => QuoteTypes::SAVINGS->id(),
            'quote_status_id' => 14,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quoteDocumentService = Mockery::mock(QuoteDocumentService::class);
        $quoteDocumentService->shouldNotReceive('getDocumentUrl');

        OcrHttpFakeHelper::fakeHealthFail(503, ['message' => 'down']);

        $service = new OCRService($quoteDocumentService, new OcrLogService);

        $eloquentQuote = PersonalQuote::on('sqlite')->findOrFail($quoteId);
        $eloquentDocType = DocumentType::on('sqlite')->where('code', 'SAV_PP')->firstOrFail();

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
            ->latest('id')
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->status)->toBe('failed')
            ->and($log->error_message)->toBe('OCR service unavailable');
    });

    test('quote status gate: returns null and sends no HTTP requests', function () {
        TestDataSeeder::seedSavingsDocumentType('SAV_PP', 'Passport');

        $quoteDocumentService = Mockery::mock(QuoteDocumentService::class);
        $quoteDocumentService->shouldNotReceive('getDocumentUrl');

        $service = new OCRService($quoteDocumentService, new OcrLogService);

        $documentType = DocumentType::on('sqlite')->where('code', 'SAV_PP')->firstOrFail();

        $quote = new class extends Model
        {
            protected $table = 'personal_quotes';
            public int $quote_status_id = 14;
        };
        $quote->setAttribute('uuid', 'X85DUBM9');
        $quote->setAttribute('code', 'SAV-X85DUBM9');

        $result = $service->process(
            QuoteTypes::SAVINGS,
            $quote,
            $documentType,
            'documents/savings/passport.pdf',
            'application/pdf',
            0,
            true,
            false,
            0
        );

        expect($result)->toBeNull();

        OcrHttpFakeHelper::assertNothingSent();
    });

    test('document type disabled for quote type: returns null (skipped) and never calls /process-document', function () {
        // Use DL as a document type that is not enabled for Savings.
        TestDataSeeder::seedDocumentType('DL', 'Driving License', 'QUOTE');

        $quoteId = DB::connection('sqlite')->table('personal_quotes')->insertGetId([
            'uuid' => 'X85DUBM9',
            'code' => 'SAV-X85DUBM9',
            'quote_type_id' => QuoteTypes::SAVINGS->id(),
            'quote_status_id' => 14,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quoteDocumentService = Mockery::mock(QuoteDocumentService::class);
        $quoteDocumentService->shouldNotReceive('getDocumentUrl');

        OcrHttpFakeHelper::fakeHealthOk();

        $service = new OCRService($quoteDocumentService, new OcrLogService);

        $eloquentQuote = PersonalQuote::on('sqlite')->findOrFail($quoteId);
        $eloquentDocType = DocumentType::on('sqlite')->where('code', 'DL')->firstOrFail();

        $result = $service->process(
            QuoteTypes::SAVINGS,
            $eloquentQuote,
            $eloquentDocType,
            'documents/savings/dl.pdf',
            'application/pdf',
            0,
            true,
            false,
            0
        );

        expect($result)->toBeNull();

        Http::assertSent(function (Request $request): bool {
            return $request->url() === rtrim((string) config('constants.OCR_API_ENDPOINT'), '/').'/health';
        });
        Http::assertNotSent(function (Request $request): bool {
            return str_ends_with($request->url(), '/process-document');
        });
    });

    test('missing document URL: returns false and logs failed', function () {
        TestDataSeeder::seedSavingsDocumentType('SAV_PP', 'Passport');

        $quoteId = DB::connection('sqlite')->table('personal_quotes')->insertGetId([
            'uuid' => 'X85DUBM9',
            'code' => 'SAV-X85DUBM9',
            'quote_type_id' => QuoteTypes::SAVINGS->id(),
            'quote_status_id' => 14,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quoteDocumentService = Mockery::mock(QuoteDocumentService::class);
        $quoteDocumentService
            ->shouldReceive('getDocumentUrl')
            ->once()
            ->andReturn('');

        OcrHttpFakeHelper::fakeHealthOk();

        $service = new OCRService($quoteDocumentService, new OcrLogService);

        $eloquentQuote = PersonalQuote::on('sqlite')->findOrFail($quoteId);
        $eloquentDocType = DocumentType::on('sqlite')->where('code', 'SAV_PP')->firstOrFail();

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
            ->latest('id')
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->status)->toBe('failed')
            ->and($log->error_message)->toBe('Document file not found in storage');
    });
});

describe('OcrValidator::isProviderEligibleForOcr', function () {
    test('Savings: returns true without provider code (skip list)', function () {
        $service = new OCRService(Mockery::mock(QuoteDocumentService::class), new OcrLogService);

        $quote = new class extends Model
        {
            public Collection $payments;
        };
        $quote->payments = collect();

        expect($service->isProviderEligibleForOcr(QuoteTypes::SAVINGS, $quote))->toBeTrue();
    });

    test('Non-skip quote type: returns false when provider code cannot be resolved', function () {
        $service = new OCRService(Mockery::mock(QuoteDocumentService::class), new OcrLogService);

        $quote = new class extends Model
        {
            public Collection $payments;
        };
        $quote->payments = collect();
        $quote->setAttribute('uuid', 'TEST-UUID');

        expect($service->isProviderEligibleForOcr(QuoteTypes::CAR, $quote))->toBeFalse();
    });

    test('Non-skip quote type: returns true when provider code resolves to supported mapping', function () {
        $service = new OCRService(Mockery::mock(QuoteDocumentService::class), new OcrLogService);

        $quote = new class extends Model
        {
            public Collection $payments;
        };

        $quote->payments = collect([
            (object) [
                'insuranceProvider' => (object) [
                    'code' => 'RSA',
                ],
            ],
        ]);
        $quote->setAttribute('uuid', 'TEST-UUID');

        expect($service->isProviderEligibleForOcr(QuoteTypes::CAR, $quote))->toBeTrue();
    });
});
