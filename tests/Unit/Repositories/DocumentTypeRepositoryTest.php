<?php

declare(strict_types=1);

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Models\TravelQuote;
use App\Repositories\DocumentTypeRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\SchemaUtils;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    Schema::table('document_types', function (Blueprint $table) {
        if (! Schema::hasColumn('document_types', 'category')) {
            $table->string('category')->nullable();
        }
        if (! Schema::hasColumn('document_types', 'is_required')) {
            $table->boolean('is_required')->default(0);
        }
        if (! Schema::hasColumn('document_types', 'is_required_for_send_policy')) {
            $table->boolean('is_required_for_send_policy')->default(0);
        }
        if (! Schema::hasColumn('document_types', 'business_type_of_insurance_id')) {
            $table->unsignedBigInteger('business_type_of_insurance_id')->nullable();
        }
    });
});

/**
 * DocumentTypeRepository::validateSendPolicyDocsUploaded — same logic as SendBookPolicyRequest lines 52–62.
 *
 * QA mapping: Car (DOC-LOB-05..06), Travel (DOC-LOB-07 multi-LOB), Business BTI (DOC-LOB-08..10).
 */
describe('DocumentTypeRepository', function () {
    describe('Car LOB', function () {
        test('returns true when all required send-policy documents are uploaded', function () {
            $code = 'SPV_TEST_1';

            DocumentType::factory()->create([
                'code' => $code,
                'text' => 'Test send policy doc',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Car,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required' => 1,
                'is_required_for_send_policy' => 1,
                'sort_order' => 1,
            ]);

            $quote = CarQuote::factory()->create([
                'customer_id' => null,
            ]);

            QuoteDocument::factory()
                ->forQuote($quote->id, CarQuote::class)
                ->ofType($code)
                ->create();

            $repository = new DocumentTypeRepository;

            expect($repository->validateSendPolicyDocsUploaded($quote, 'Car'))->toBeTrue();
        });

        test('returns false when a required send-policy document is missing', function () {
            DocumentType::factory()->create([
                'code' => 'SPV_CAR_MISSING',
                'text' => 'Required car doc',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Car,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required' => 1,
                'is_required_for_send_policy' => 1,
                'sort_order' => 1,
            ]);

            $quote = CarQuote::factory()->create(['customer_id' => null]);

            $repository = new DocumentTypeRepository;

            expect($repository->validateSendPolicyDocsUploaded($quote, 'Car'))->toBeFalse();
        });
    });

    describe('Travel LOB (multiple LOBs segregation)', function () {
        test('uses Travel quote_type_id requirements, distinct from Car', function () {
            $travelCode = 'SPV_TRAVEL_1';

            DocumentType::factory()->create([
                'code' => $travelCode,
                'text' => 'Travel send policy required',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Travel,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required' => 1,
                'is_required_for_send_policy' => 1,
                'sort_order' => 1,
            ]);

            $quote = TravelQuote::query()->create([
                'uuid' => (string) Str::uuid(),
                'code' => 'TQ-SPV-1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            QuoteDocument::factory()
                ->forQuote($quote->id, TravelQuote::class)
                ->ofType($travelCode)
                ->create();

            $repository = new DocumentTypeRepository;

            expect($repository->validateSendPolicyDocsUploaded($quote, 'Travel'))->toBeTrue();
        });
    });

    describe('Business LOB and business_type_of_insurance_id', function () {
        beforeEach(function () {
            SchemaUtils::ensureTable('business_quote_request', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('business_type_of_insurance_id')->nullable();
                $table->string('email')->nullable();
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->timestamps();
            });
        });

        test('scopes required documents to the quote business_type_of_insurance_id (wrong BTI upload fails)', function () {
            $btiForQuote = 501;
            $otherBti = 502;

            DocumentType::factory()->create([
                'code' => 'BUS_REQ_501',
                'text' => 'Required for BTI 501',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Business,
                'business_type_of_insurance_id' => $btiForQuote,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required' => 1,
                'is_required_for_send_policy' => 1,
                'sort_order' => 1,
            ]);

            DocumentType::factory()->create([
                'code' => 'BUS_REQ_502',
                'text' => 'Required for BTI 502',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Business,
                'business_type_of_insurance_id' => $otherBti,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required' => 1,
                'is_required_for_send_policy' => 1,
                'sort_order' => 2,
            ]);

            $quote = BusinessQuote::query()->create([
                'uuid' => (string) Str::uuid(),
                'code' => 'BQ-SPV-501',
                'business_type_of_insurance_id' => $btiForQuote,
                'email' => 'biz@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            QuoteDocument::factory()
                ->forQuote($quote->id, BusinessQuote::class)
                ->ofType('BUS_REQ_502')
                ->create();

            $repository = new DocumentTypeRepository;

            expect($repository->validateSendPolicyDocsUploaded($quote, 'Business'))->toBeFalse();
        });

        test('returns true when uploaded documents match the quote business_type_of_insurance_id', function () {
            $btiForQuote = 601;

            DocumentType::factory()->create([
                'code' => 'BUS_REQ_601',
                'text' => 'Required for BTI 601',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Business,
                'business_type_of_insurance_id' => $btiForQuote,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required' => 1,
                'is_required_for_send_policy' => 1,
                'sort_order' => 1,
            ]);

            $quote = BusinessQuote::query()->create([
                'uuid' => (string) Str::uuid(),
                'code' => 'BQ-SPV-601',
                'business_type_of_insurance_id' => $btiForQuote,
                'email' => 'biz@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            QuoteDocument::factory()
                ->forQuote($quote->id, BusinessQuote::class)
                ->ofType('BUS_REQ_601')
                ->create();

            $repository = new DocumentTypeRepository;

            expect($repository->validateSendPolicyDocsUploaded($quote, 'Business'))->toBeTrue();
        });

        test('segregates a third business_type_of_insurance_id from other BTI requirements', function () {
            $btiA = 801;
            $btiB = 802;
            $btiC = 803;

            foreach (['BUS_REQ_801' => $btiA, 'BUS_REQ_802' => $btiB, 'BUS_REQ_803' => $btiC] as $code => $bti) {
                DocumentType::factory()->create([
                    'code' => $code,
                    'text' => "Required for BTI {$bti}",
                    'is_active' => 1,
                    'quote_type_id' => QuoteTypeId::Business,
                    'business_type_of_insurance_id' => $bti,
                    'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                    'is_required' => 1,
                    'is_required_for_send_policy' => 1,
                    'sort_order' => 1,
                ]);
            }

            $quote = BusinessQuote::query()->create([
                'uuid' => (string) Str::uuid(),
                'code' => 'BQ-SPV-803',
                'business_type_of_insurance_id' => $btiC,
                'email' => 'biz@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            QuoteDocument::factory()
                ->forQuote($quote->id, BusinessQuote::class)
                ->ofType('BUS_REQ_803')
                ->create();

            $repository = new DocumentTypeRepository;

            expect($repository->validateSendPolicyDocsUploaded($quote, 'Business'))->toBeTrue();
        });
    });
});
