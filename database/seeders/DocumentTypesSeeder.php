<?php

namespace Database\Seeders;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPoliticalViolenceTerrorismBusinessDocumentTypes();
    }

    /**
     * Document types for Business quote type, Political Violence & Terrorism insurance (id 37),
     * for company (CBTC) and individual (IBTC) business customers — aligned with production seed.
     */
    private function seedPoliticalViolenceTerrorismBusinessDocumentTypes(): void
    {
        $accepted = '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png';
        $pvtiId = BusinessTypeOfInsuranceIdEnum::POLITICAL_VIOLENCE_AND_TERRORISM_INSURANCE;

        foreach ([DocumentTypeCode::COMPANY_BUSINESS_TYPE_OF_CUSTOMER, DocumentTypeCode::INDIVIDUAL_BUSINESS_TYPE_OF_CUSTOMER] as $customerType) {
            $rows = [
                // Quote
                ['code' => 'TCOMP_VISA', 'text' => 'Visa', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::QUOTE, 'is_required_for_send_policy' => 0],
                ['code' => 'TCOMP_MO', 'text' => 'Passport', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::QUOTE, 'is_required_for_send_policy' => 0],
                ['code' => 'TCOMP_EI', 'text' => 'Emirates ID', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 1, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 1, 'category' => DocumentTypeCategory::QUOTE, 'is_required_for_send_policy' => 0],
                ['code' => 'TCOMP_TL', 'text' => 'Trade License', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 1, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 1, 'category' => DocumentTypeCategory::QUOTE, 'is_required_for_send_policy' => 0],
                ['code' => 'TCOMP_EC', 'text' => 'Establishment Card', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::QUOTE, 'is_required_for_send_policy' => 0],
                ['code' => 'TCOMP_VAT', 'text' => 'TRN / VAT Certificate /Undertaking letter for non-VAT', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::QUOTE, 'is_required_for_send_policy' => 0],
                ['code' => 'TCOMP_TOB', 'text' => 'Signed Quote or TOB', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::QUOTE, 'is_required_for_send_policy' => 0],
                ['code' => 'TCOMP_FTC', 'text' => 'Final Terms & Conditions', 'description' => null, 'is_active' => 0, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::QUOTE, 'is_required_for_send_policy' => 0],
                ['code' => 'TCOMP_BAL', 'text' => 'Broker appointment letter', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::QUOTE, 'is_required_for_send_policy' => 0],
                ['code' => 'TCOMP_MOA', 'text' => 'Memorandum of agreement', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::QUOTE, 'is_required_for_send_policy' => 0],
                ['code' => 'TCOMP_MOAAA', 'text' => 'Memorandum of association or Articles of Association', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 10, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::QUOTE, 'is_required_for_send_policy' => 0],
                ['code' => 'TCOMP_KYC', 'text' => 'KYC Requirements', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 10, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::QUOTE, 'is_required_for_send_policy' => 0],
                ['code' => 'TCOMP_OTHER', 'text' => 'Others', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 10, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::QUOTE, 'is_required_for_send_policy' => 0],
                // Issuing (max_size 2 per production SQL)
                ['code' => 'TCOMP_PS', 'text' => 'Policy Schedule', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 2, 'is_required' => 1, 'send_to_customer' => 1, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ISSUING_DOCUMENTS, 'is_required_for_send_policy' => 1],
                ['code' => 'TCOMP_PC', 'text' => 'Policy Certificate', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 2, 'is_required' => 1, 'send_to_customer' => 1, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ISSUING_DOCUMENTS, 'is_required_for_send_policy' => $customerType === DocumentTypeCode::COMPANY_BUSINESS_TYPE_OF_CUSTOMER ? 0 : 1],
                ['code' => DocumentTypeCode::TI, 'text' => 'Tax Invoice', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 2, 'is_required' => 1, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ISSUING_DOCUMENTS, 'is_required_for_send_policy' => 1],
                ['code' => DocumentTypeCode::CTIRBB, 'text' => 'Tax Invoice Raised by Buyer', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 2, 'is_required' => 1, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ISSUING_DOCUMENTS, 'is_required_for_send_policy' => 1],
                ['code' => 'TCOMP_AEA', 'text' => 'Additional Email Attachments', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 2, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ISSUING_DOCUMENTS, 'is_required_for_send_policy' => 0],
                ['code' => 'TCOMP_PH', 'text' => 'Policy handbook', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 2, 'is_required' => 1, 'send_to_customer' => 1, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ISSUING_DOCUMENTS, 'is_required_for_send_policy' => 0],
                // Endorsement
                ['code' => 'TBUS_CD', 'text' => 'Customer documents', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 1, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ENDORSEMENT_DOCUMENTS, 'is_required_for_send_policy' => 0],
                ['code' => 'TBUS_UWEC', 'text' => 'UW email Correspondence', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 1, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ENDORSEMENT_DOCUMENTS, 'is_required_for_send_policy' => 0],
                ['code' => 'TBUS_PSLIP', 'text' => 'Payment Slip', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 1, 'send_to_customer' => 1, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ENDORSEMENT_DOCUMENTS, 'is_required_for_send_policy' => 0],
                ['code' => 'TBUS_REC', 'text' => 'Receipt (Insurer)', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ENDORSEMENT_DOCUMENTS, 'is_required_for_send_policy' => 0],
                ['code' => 'TBUS_PP', 'text' => 'Payment Proof (Insurer collects)', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 1, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ENDORSEMENT_DOCUMENTS, 'is_required_for_send_policy' => 0],
                ['code' => 'TBUS_ES', 'text' => 'Endorsed Schedule', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 1, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ENDORSEMENT_DOCUMENTS, 'is_required_for_send_policy' => 0],
                ['code' => 'TBUS_EC', 'text' => 'Endorsed Certificate', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 1, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ENDORSEMENT_DOCUMENTS, 'is_required_for_send_policy' => 0],
                ['code' => 'TBUS_TI', 'text' => 'Tax Invoice', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 1, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ENDORSEMENT_DOCUMENTS, 'is_required_for_send_policy' => 0],
                ['code' => 'TBUS_RSU', 'text' => 'Receipt Send Update', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 5, 'max_size' => 25, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ENDORSEMENT_DOCUMENTS, 'is_required_for_send_policy' => 0],
                // Second COMP_EC row: E-card (Health) — same code as Establishment Card; distinguished by category
                ['code' => 'TCOMP_EC', 'text' => 'E-card(Health)', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 10, 'max_size' => 2, 'is_required' => 0, 'send_to_customer' => 0, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ISSUING_DOCUMENTS, 'is_required_for_send_policy' => 0],
                ['code' => 'TBUS_ECSU', 'text' => 'E-card', 'description' => null, 'is_active' => 1, 'quote_type_id' => QuoteTypeId::Business, 'folder_path' => 'business', 'accepted_files' => $accepted, 'max_files' => 10, 'max_size' => 2, 'is_required' => 0, 'send_to_customer' => 1, 'sort_order' => null, 'receive_from_customer' => 0, 'category' => DocumentTypeCategory::ENDORSEMENT_DOCUMENTS, 'is_required_for_send_policy' => 0],
            ];

            foreach ($rows as $document) {
                DocumentType::firstOrCreate(
                    [
                        'code' => $document['code'],
                        'quote_type_id' => $document['quote_type_id'],
                        'business_type_of_insurance_id' => $pvtiId,
                        'business_type_of_customer' => $customerType,
                        'category' => $document['category'],
                    ],
                    array_merge($document, [
                        'business_type_of_insurance_id' => $pvtiId,
                        'business_type_of_customer' => $customerType,
                        'tool_tip' => null,
                        'registration_type' => null,
                        'vehicle_use' => null,
                    ])
                );
            }
        }
    }
}
