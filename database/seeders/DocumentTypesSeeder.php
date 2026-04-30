<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Seeds LOB-specific rows in {@see DocumentType::$table document_types}.
 */
class DocumentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentTypes = [
            [
                'code' => DocumentTypeCode::COMPLIANCE_APPROVAL,
                'text' => 'Compliance Approvals (Restricted- internal confidential document)',
                'description' => 'internal restricted doc (hidden from client)',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Car,
                'folder_path' => 'car',
                'accepted_files' => '.pdf',
                'max_files' => 10,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCategory::QUOTE,
                'is_required_for_send_policy' => 0,
                'sort_order' => null,
                'is_restricted_internal_document' => 1,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
        ];

        Model::unguarded(function () use ($documentTypes): void {
            foreach ($documentTypes as $documentType) {
                DocumentType::firstOrCreate(
                    [
                        'code' => $documentType['code'],
                        'quote_type_id' => $documentType['quote_type_id'],
                    ],
                    $documentType
                );
            }
        });
    }
}
