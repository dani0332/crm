<?php

declare(strict_types=1);

use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Database\Seeders\DocumentTypesSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    DB::connection('sqlite')->table('document_types')->delete();
});

it('seeds car compliance approval document type', function () {
    (new DocumentTypesSeeder)->run();

    $documentType = DocumentType::query()
        ->where('code', DocumentTypeCode::COMPLIANCE_APPROVAL)
        ->where('quote_type_id', QuoteTypeId::Car)
        ->first();

    expect($documentType)->not->toBeNull()
        ->and($documentType->text)->toContain('Compliance Approvals')
        ->and($documentType->description)->toContain('internal restricted')
        ->and((int) $documentType->is_active)->toBe(1)
        ->and((int) $documentType->quote_type_id)->toBe(QuoteTypeId::Car)
        ->and($documentType->folder_path)->toBe('car')
        ->and($documentType->accepted_files)->toBe('.pdf')
        ->and((int) $documentType->max_files)->toBe(10)
        ->and((int) $documentType->max_size)->toBe(25)
        ->and((int) $documentType->is_required)->toBe(0)
        ->and((int) $documentType->send_to_customer)->toBe(0)
        ->and((int) $documentType->receive_from_customer)->toBe(0)
        ->and($documentType->category)->toBe(DocumentTypeCategory::QUOTE)
        ->and((int) $documentType->is_required_for_send_policy)->toBe(0)
        ->and($documentType->sort_order)->toBeNull()
        ->and((int) $documentType->is_restricted_internal_document)->toBe(1);
});

it('is idempotent when run twice', function () {
    (new DocumentTypesSeeder)->run();
    (new DocumentTypesSeeder)->run();

    expect(
        DocumentType::query()
            ->where('code', DocumentTypeCode::COMPLIANCE_APPROVAL)
            ->where('quote_type_id', QuoteTypeId::Car)
            ->count()
    )->toBe(1);
});
