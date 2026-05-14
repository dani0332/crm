<?php

declare(strict_types=1);

use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Database\Seeders\DocumentTypesSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

/**
 * DocumentTypesSeeder::seedDeviceDocumentTypes runs outside Model::unguarded; DocumentType does not
 * list `code` as fillable. Unguard only for the seeder run.
 */
function runDocumentTypesSeeder(): void
{
    Model::unguarded(fn () => (new DocumentTypesSeeder)->run());
}

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    DB::connection('sqlite')->table('document_types')->delete();
    DB::connection('sqlite')->table('business_type_of_insurance')->delete();
});

it('seeds compliance approval document type for each configured non-business quote type', function () {
    runDocumentTypesSeeder();

    $nonBusiness = DocumentType::query()
        ->where('code', DocumentTypeCode::COMPLIANCE_APPROVAL)
        ->where('quote_type_id', '!=', QuoteTypeId::Business)
        ->orderBy('quote_type_id')
        ->get();

    expect($nonBusiness)->toHaveCount(14);

    $car = $nonBusiness->firstWhere('quote_type_id', QuoteTypeId::Car);
    expect($car)->not->toBeNull()
        ->and($car->folder_path)->toBe('car')
        ->and($car->text)->toContain('Compliance Approvals')
        ->and((int) $car->is_restricted_internal_document)->toBe(1)
        ->and($car->category)->toBe(DocumentTypeCategory::QUOTE)
        ->and($car->business_type_of_insurance_id)->toBeNull()
        ->and($car->business_type_of_customer)->toBeNull();

    $travel = $nonBusiness->firstWhere('quote_type_id', QuoteTypeId::Travel);
    expect($travel)->not->toBeNull()
        ->and($travel->folder_path)->toBe('travel');
});

it('seeds business compliance rows per active insurance type for IBTC and CBTC', function () {
    $db = DB::connection('sqlite');
    $db->table('business_type_of_insurance')->insert([
        ['code' => 'BTI_A', 'text' => 'Type A', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['code' => 'BTI_B', 'text' => 'Type B', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
    ]);

    runDocumentTypesSeeder();

    $businessRows = DocumentType::query()
        ->where('code', DocumentTypeCode::COMPLIANCE_APPROVAL)
        ->where('quote_type_id', QuoteTypeId::Business)
        ->orderBy('business_type_of_insurance_id')
        ->orderBy('business_type_of_customer')
        ->get();

    expect($businessRows)->toHaveCount(4);

    $expectedInsuranceIds = $db->table('business_type_of_insurance')->where('is_active', 1)->orderBy('id')->pluck('id')->sort()->values()->all();

    $ibtc = $businessRows->where('business_type_of_customer', DocumentTypeCode::INDIVIDUAL_BUSINESS_TYPE_OF_CUSTOMER);
    expect($ibtc)->toHaveCount(2)
        ->and($ibtc->pluck('business_type_of_insurance_id')->sort()->values()->all())->toBe($expectedInsuranceIds);

    $cbtc = $businessRows->where('business_type_of_customer', DocumentTypeCode::COMPANY_BUSINESS_TYPE_OF_CUSTOMER);
    expect($cbtc)->toHaveCount(2)
        ->and($cbtc->pluck('business_type_of_insurance_id')->sort()->values()->all())->toBe($expectedInsuranceIds);

    foreach ($businessRows as $row) {
        expect($row->folder_path)->toBe('business');
    }
});

it('is idempotent when run twice', function () {
    DB::connection('sqlite')->table('business_type_of_insurance')->insert([
        ['code' => 'BTI_A', 'text' => 'Type A', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
    ]);

    runDocumentTypesSeeder();
    runDocumentTypesSeeder();

    expect(
        DocumentType::query()
            ->where('code', DocumentTypeCode::COMPLIANCE_APPROVAL)
            ->where('quote_type_id', '!=', QuoteTypeId::Business)
            ->count()
    )->toBe(14);

    expect(
        DocumentType::query()
            ->where('code', DocumentTypeCode::COMPLIANCE_APPROVAL)
            ->where('quote_type_id', QuoteTypeId::Business)
            ->count()
    )->toBe(2);
});
