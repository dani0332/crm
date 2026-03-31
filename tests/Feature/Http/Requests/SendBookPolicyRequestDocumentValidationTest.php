<?php

declare(strict_types=1);

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\SendPolicyTypeEnum;
use App\Http\Requests\SendBookPolicyRequest;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator as ValidatorInstance;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\SchemaUtils;

/** Matches SendBookPolicyRequest::withValidator document gate message. */
const SBP_REQUIRED_DOCUMENTS_MESSAGE = 'Required documents are not uploaded';

/**
 * Builds a validator after SendBookPolicyRequest::withValidator (same pattern as automation services).
 */
function sendBookPolicyValidator(array $payload): ValidatorInstance
{
    $baseRequest = Request::create('/send-book-policy-test', 'POST', $payload);
    $formRequest = SendBookPolicyRequest::createFrom($baseRequest);
    $formRequest->setContainer(app());

    $validator = Validator::make($formRequest->all(), $formRequest->rules());
    $formRequest->withValidator($validator);

    return $validator;
}

/**
 * Covers SendBookPolicyRequest::withValidator lines 52–62 (customer/sage required-document gate).
 *
 * QA mapping: DOC-LOB-01..03 (gating), DOC-LOB-05..07 (Car), DOC-LOB-08..09 (Business).
 */
describe('SendBookPolicyRequest document validation (customer / sage)', function () {
    describe('send_policy_type gating (DOC-LOB-01..03)', function () {
        test('does not run document gate when send_policy_type is not customer or sage', function () {
            $advisor = User::factory()->create();
            $quote = CarQuote::factory()->create([
                'customer_id' => null,
                'advisor_id' => $advisor->id,
                'email' => 'insured@example.com',
            ]);

            $validator = sendBookPolicyValidator([
                'model_type' => 'Car',
                'quote_id' => $quote->id,
                'send_policy_type' => 'internal_review',
            ]);

            $validator->validate();

            expect(collect($validator->errors()->get('error', [])))->not->toContain(SBP_REQUIRED_DOCUMENTS_MESSAGE);
        });
    });

    describe('Car LOB (DOC-LOB-05..07)', function () {
        test('adds required documents error for customer when car quote is missing send-policy documents', function () {
            $advisor = User::factory()->create();

            DocumentType::factory()->create([
                'code' => 'SBP_CAR_REQ_1',
                'text' => 'Car send policy required',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Car,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required' => 1,
                'is_required_for_send_policy' => 1,
                'sort_order' => 1,
            ]);

            $quote = CarQuote::factory()->create([
                'customer_id' => null,
                'advisor_id' => $advisor->id,
                'email' => 'insured@example.com',
            ]);

            $validator = sendBookPolicyValidator([
                'model_type' => 'Car',
                'quote_id' => $quote->id,
                'send_policy_type' => SendPolicyTypeEnum::CUSTOMER,
            ]);

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->get('error'))->toContain(SBP_REQUIRED_DOCUMENTS_MESSAGE);
        });

        test('does not add required documents error for customer when car quote has all required send-policy documents', function () {
            $advisor = User::factory()->create();

            DocumentType::factory()->create([
                'code' => 'SBP_CAR_REQ_2',
                'text' => 'Car send policy required',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Car,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required' => 1,
                'is_required_for_send_policy' => 1,
                'sort_order' => 1,
            ]);

            $quote = CarQuote::factory()->create([
                'customer_id' => null,
                'advisor_id' => $advisor->id,
                'email' => 'insured@example.com',
            ]);

            QuoteDocument::factory()
                ->forQuote($quote->id, CarQuote::class)
                ->ofType('SBP_CAR_REQ_2')
                ->create();

            $validator = sendBookPolicyValidator([
                'model_type' => 'Car',
                'quote_id' => $quote->id,
                'send_policy_type' => SendPolicyTypeEnum::CUSTOMER,
            ]);

            expect(collect($validator->errors()->get('error', [])))->not->toContain(SBP_REQUIRED_DOCUMENTS_MESSAGE);
        });

        test('does not add document error from this gate when quote cannot be resolved (DOC-LOB-04)', function () {
            $validator = sendBookPolicyValidator([
                'model_type' => 'Car',
                'quote_id' => 999999999,
                'send_policy_type' => SendPolicyTypeEnum::CUSTOMER,
            ]);

            expect($validator->fails())->toBeTrue();
            expect(collect($validator->errors()->get('error', [])))->not->toContain(SBP_REQUIRED_DOCUMENTS_MESSAGE)
                ->and($validator->errors()->get('error'))->toContain('Quote not found');
        });
    });

    describe('Business LOB and business_type_of_insurance_id (DOC-LOB-08..09)', function () {
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

        test('adds required documents error for customer when business quote is missing required docs for its business_type_of_insurance_id', function () {
            $advisor = User::factory()->create();
            $bti = 701;

            DocumentType::factory()->create([
                'code' => 'SBP_BUS_REQ_701',
                'text' => 'Business send policy required for BTI 701',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Business,
                'business_type_of_insurance_id' => $bti,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required' => 1,
                'is_required_for_send_policy' => 1,
                'sort_order' => 1,
            ]);

            $quote = BusinessQuote::query()->create([
                'uuid' => (string) Str::uuid(),
                'code' => 'BQ-SBP-701',
                'business_type_of_insurance_id' => $bti,
                'email' => 'biz@example.com',
                'advisor_id' => $advisor->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $validator = sendBookPolicyValidator([
                'model_type' => 'Business',
                'quote_id' => $quote->id,
                'send_policy_type' => SendPolicyTypeEnum::CUSTOMER,
            ]);

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->get('error'))->toContain(SBP_REQUIRED_DOCUMENTS_MESSAGE);
        });

        test('does not add required documents error when business quote has documents for its business_type_of_insurance_id', function () {
            $advisor = User::factory()->create();
            $bti = 702;

            DocumentType::factory()->create([
                'code' => 'SBP_BUS_REQ_702',
                'text' => 'Business send policy required for BTI 702',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Business,
                'business_type_of_insurance_id' => $bti,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required' => 1,
                'is_required_for_send_policy' => 1,
                'sort_order' => 1,
            ]);

            $quote = BusinessQuote::query()->create([
                'uuid' => (string) Str::uuid(),
                'code' => 'BQ-SBP-702',
                'business_type_of_insurance_id' => $bti,
                'email' => 'biz@example.com',
                'advisor_id' => $advisor->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            QuoteDocument::factory()
                ->forQuote($quote->id, BusinessQuote::class)
                ->ofType('SBP_BUS_REQ_702')
                ->create();

            $validator = sendBookPolicyValidator([
                'model_type' => 'Business',
                'quote_id' => $quote->id,
                'send_policy_type' => SendPolicyTypeEnum::CUSTOMER,
            ]);

            expect(collect($validator->errors()->get('error', [])))->not->toContain(SBP_REQUIRED_DOCUMENTS_MESSAGE);
        });
    });
});
