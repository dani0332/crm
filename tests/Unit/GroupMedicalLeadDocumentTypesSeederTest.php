<?php

declare(strict_types=1);

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Database\Seeders\GroupMedicalLeadDocumentTypesSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    DB::connection('sqlite')->table('document_types')->delete();
});

it('seeds group medical IMCRM lead document types for business quote type and group medical insurance', function () {
    (new GroupMedicalLeadDocumentTypesSeeder)->run();

    $codes = [
        DocumentTypeCode::GM_IMCRM_CENSUS_LIST,
        DocumentTypeCode::GM_IMCRM_TABLE_OF_BENEFITS,
        DocumentTypeCode::GM_IMCRM_TRADE_LICENSE,
        DocumentTypeCode::GM_IMCRM_DHA_REPORT,
        DocumentTypeCode::GM_IMCRM_OTHER,
    ];

    foreach ($codes as $code) {
        $row = DocumentType::query()->where('code', $code)->first();
        expect($row)->not->toBeNull()
            ->and((int) $row->quote_type_id)->toBe(QuoteTypeId::Business)
            ->and((int) $row->business_type_of_insurance_id)->toBe(BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL)
            ->and($row->business_type_of_customer)->toBeNull()
            ->and($row->category)->toBe(DocumentTypeCategory::QUOTE)
            ->and((int) $row->receive_from_customer)->toBe(1)
            ->and($row->folder_path)->toBe('group-medical');
    }

    expect(DocumentType::query()->whereIn('code', $codes)->count())->toBe(5);
});

it('is idempotent when run twice', function () {
    (new GroupMedicalLeadDocumentTypesSeeder)->run();
    (new GroupMedicalLeadDocumentTypesSeeder)->run();

    expect(
        DocumentType::query()->where('code', DocumentTypeCode::GM_IMCRM_CENSUS_LIST)->count()
    )->toBe(1);
});
