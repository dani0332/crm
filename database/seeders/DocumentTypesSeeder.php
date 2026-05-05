<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\BusinessTypeOfInsurance;
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
        Model::unguarded(function (): void {
            $this->seedNonBusinessComplianceApprovalDocumentTypes();
            $this->seedBusinessComplianceApprovalDocumentTypes();
        });
    }

    /**
     * Compliance-approval document types for LOBs other than business (single row per quote type).
     */
    private function seedNonBusinessComplianceApprovalDocumentTypes(): void
    {
        $baseAttributes = $this->complianceApprovalBaseAttributes();

        foreach ($this->complianceApprovalDocumentTargets() as $target) {
            $documentType = array_merge($baseAttributes, [
                'quote_type_id' => $target['quote_type_id'],
                'folder_path' => $target['folder_path'],
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ]);

            DocumentType::firstOrCreate(
                [
                    'code' => $documentType['code'],
                    'quote_type_id' => $documentType['quote_type_id'],
                    'business_type_of_insurance_id' => null,
                    'business_type_of_customer' => null,
                ],
                $documentType
            );
        }
    }

    /**
     * Business quote type: one compliance-approval row per active {@see BusinessTypeOfInsurance}
     * for individual (IBTC) and company (CBTC) customer segments.
     */
    private function seedBusinessComplianceApprovalDocumentTypes(): void
    {
        $baseAttributes = array_merge($this->complianceApprovalBaseAttributes(), [
            'quote_type_id' => QuoteTypeId::Business,
            'folder_path' => 'business',
        ]);

        $insuranceTypeIds = BusinessTypeOfInsurance::query()
            ->where('is_active', 1)
            ->orderBy('id')
            ->pluck('id');

        $customerSegments = [
            DocumentTypeCode::INDIVIDUAL_BUSINESS_TYPE_OF_CUSTOMER,
            DocumentTypeCode::COMPANY_BUSINESS_TYPE_OF_CUSTOMER,
        ];

        foreach ($insuranceTypeIds as $businessTypeOfInsuranceId) {
            foreach ($customerSegments as $businessTypeOfCustomer) {
                $documentType = array_merge($baseAttributes, [
                    'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                    'business_type_of_customer' => $businessTypeOfCustomer,
                ]);

                DocumentType::firstOrCreate(
                    [
                        'code' => $documentType['code'],
                        'quote_type_id' => $documentType['quote_type_id'],
                        'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                        'business_type_of_customer' => $businessTypeOfCustomer,
                    ],
                    $documentType
                );
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function complianceApprovalBaseAttributes(): array
    {
        return [
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
        ];
    }

    /**
     * Non-business quote types: one compliance-approval row each (folder matches LOB storage).
     *
     * @return list<array{quote_type_id: int, folder_path: string}>
     */
    private function complianceApprovalDocumentTargets(): array
    {
        return [
            ['quote_type_id' => QuoteTypeId::Car, 'folder_path' => 'car'],
            ['quote_type_id' => QuoteTypeId::Home, 'folder_path' => 'home'],
            ['quote_type_id' => QuoteTypeId::Health, 'folder_path' => 'health'],
            ['quote_type_id' => QuoteTypeId::Life, 'folder_path' => 'life'],
            ['quote_type_id' => QuoteTypeId::Bike, 'folder_path' => 'bike'],
            ['quote_type_id' => QuoteTypeId::Yacht, 'folder_path' => 'yacht'],
            ['quote_type_id' => QuoteTypeId::Travel, 'folder_path' => 'travel'],
            ['quote_type_id' => QuoteTypeId::Pet, 'folder_path' => 'pet'],
            ['quote_type_id' => QuoteTypeId::Cycle, 'folder_path' => 'cycle'],
            ['quote_type_id' => QuoteTypeId::Jetski, 'folder_path' => 'jetski'],
            ['quote_type_id' => QuoteTypeId::TradeCredit, 'folder_path' => 'trade-credit'],
            ['quote_type_id' => QuoteTypeId::CompanyCar, 'folder_path' => 'car'],
            ['quote_type_id' => QuoteTypeId::Savings, 'folder_path' => 'savings'],
            ['quote_type_id' => QuoteTypeId::Cyber, 'folder_path' => 'cyber'],
        ];
    }
}
