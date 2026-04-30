<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
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
        $baseAttributes = [
            'code' => DocumentTypeCode::COMPLIANCE_APPROVAL,
            'text' => 'Compliance Approvals (Restricted- internal confidential document)',
            'description' => 'internal restricted doc (hidden from client)',
            'is_active' => 1,
            'accepted_files' => '.pdf,.xlsx,.docx,.jpeg,.jpg,.png',
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
        ];

        foreach ($this->complianceApprovalDocumentTargets() as $target) {
            $documentType = array_merge($baseAttributes, [
                'quote_type_id' => $target['quote_type_id'],
                'folder_path' => $target['folder_path'],
            ]);

            DocumentType::firstOrCreate(
                [
                    'code' => $documentType['code'],
                    'quote_type_id' => $documentType['quote_type_id'],
                ],
                $documentType
            );
        }
    }

    /**
     * Quote types that receive the compliance-approval document type, with storage folder per LOB.
     *
     * @return list<array{quote_type_id: int, folder_path: string}>
     */
    private function complianceApprovalDocumentTargets(): array
    {
        return [
            ['quote_type_id' => QuoteTypeId::Car,  'folder_path' => 'car'],
            ['quote_type_id' => QuoteTypeId::Home,  'folder_path' => 'home'],
            ['quote_type_id' => QuoteTypeId::Health,  'folder_path' => 'health'],
            ['quote_type_id' => QuoteTypeId::Life,  'folder_path' => 'life'],
            ['quote_type_id' => QuoteTypeId::Business,  'folder_path' => 'business'],
            ['quote_type_id' => QuoteTypeId::Bike,  'folder_path' => 'bike'],
            ['quote_type_id' => QuoteTypeId::Yacht,  'folder_path' => 'yacht'],
            ['quote_type_id' => QuoteTypeId::Travel,  'folder_path' => 'travel'],
            ['quote_type_id' => QuoteTypeId::Pet,  'folder_path' => 'pet'],
            ['quote_type_id' => QuoteTypeId::Cycle, 'folder_path' => 'cycle'],
            ['quote_type_id' => QuoteTypeId::Jetski, 'folder_path' => 'jetski'],
            ['quote_type_id' => QuoteTypeId::TradeCredit, 'folder_path' => 'trade-credit'],
            ['quote_type_id' => QuoteTypeId::CompanyCar, 'folder_path' => 'car'],
            ['quote_type_id' => QuoteTypeId::Savings, 'folder_path' => 'savings'],
            ['quote_type_id' => QuoteTypeId::Cyber, 'folder_path' => 'cyber'],
        ];
    }
}
