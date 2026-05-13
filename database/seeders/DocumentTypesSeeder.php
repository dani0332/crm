<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
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
        $this->seedDeviceDocumentTypes();
        Model::unguarded(function (): void {
            $this->seedNonBusinessComplianceApprovalDocumentTypes();
            $this->seedBusinessComplianceApprovalDocumentTypes();
        });
    }

    public function seedDeviceDocumentTypes(): void
    {
        $quoteDocuments = [
            // Quote category documents
            [
                'code' => DocumentTypeCode::DEVICE_SMARTPHONE_PAYMENT_PROOF,
                'text' => 'Payment Proof',
                'description' => null,
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Device,
                'folder_path' => strtolower(QuoteTypes::DEVICE->value),
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'max_files' => 5,
                'max_size' => 30,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => 3,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => DocumentTypeCode::DEVICE_SMARTPHONE_PAYMENT_RECEIPT,
                'text' => 'Receipt',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Device,
                'folder_path' => strtolower(QuoteTypes::DEVICE->value),
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => 2,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => DocumentTypeCode::DEVICE_SMARTPHONE_PAYMENT_DISCOUNT_PROOF,
                'text' => 'Discount Proof',
                'description' => null,
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Device,
                'folder_path' => strtolower(QuoteTypes::DEVICE->value),
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'max_files' => 5,
                'max_size' => 30,
                'is_required' => 0,
                'send_to_customer' => 1,
                'sort_order' => 1,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => DocumentTypeCode::DEVICE_SMARTPHONE_EMIRATES_ID,
                'text' => 'Emirates ID (Front side & Back side)',
                'description' => 'Please upload your valid Emirates ID license.',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Device,
                'folder_path' => strtolower(QuoteTypes::DEVICE->value),
                'accepted_files' => '.pdf,.docx,.doc,.jpeg,.jpg,.png,.webp',
                'max_files' => 2,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 0,
                'sort_order' => 4,
                'receive_from_customer' => 1,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => DocumentTypeCode::DEVICE_SMARTPHONE_OTHER_DOCUMENTS,
                'text' => 'Other documents',
                'description' => null,
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Device,
                'folder_path' => strtolower(QuoteTypes::DEVICE->value),
                'accepted_files' => '.pdf,.docx,.doc,.jpeg,.jpg,.png,.webp',
                'max_files' => 10,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => 5,
                'receive_from_customer' => 1,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_CERTIFICATE,
                'text' => 'Policy Certificate',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Device,
                'folder_path' => strtolower(QuoteTypes::DEVICE->value),
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png,.webp',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 1,
                'sort_order' => 6,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 1,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_SCHEDULE,
                'text' => 'Policy Schedule',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Device,
                'folder_path' => strtolower(QuoteTypes::DEVICE->value),
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png,.webp',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 1,
                'sort_order' => 7,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 1,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE,
                'text' => 'Tax Invoice',
                'description' => null,
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Device,
                'folder_path' => strtolower(QuoteTypes::DEVICE->value),
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png,.webp',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 0,
                'sort_order' => 8,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE_RAISED_BY_BUYER,
                'text' => 'Tax Invoice Raised By Buyer',
                'description' => null,
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Device,
                'folder_path' => strtolower(QuoteTypes::DEVICE->value),
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png,.webp',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 0,
                'sort_order' => 9,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_HANDBOOK,
                'text' => 'Policy Handbook',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Device,
                'folder_path' => strtolower(QuoteTypes::DEVICE->value),
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 4,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 1,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 1,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
                'sort_order' => 10,
            ],
        ];

        foreach ($quoteDocuments as $document) {
            DocumentType::firstOrCreate(
                [
                    'code' => $document['code'],
                    'quote_type_id' => $document['quote_type_id'],
                    'business_type_of_insurance_id' => $document['business_type_of_insurance_id'] ?? null,
                ],
                $document
            );
        }
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
