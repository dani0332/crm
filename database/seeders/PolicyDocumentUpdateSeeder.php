<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class PolicyDocumentUpdateSeeder extends Seeder
{
    protected $documentsRequiredForSendPolicy = [
        [
            'quote_type_id' => QuoteTypeId::Car,
            'document_codes' => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::CPC,
                DocumentTypeCode::PHB,
            ],
            'is_required_for_send_policy' => 1,
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
        ],
        [
            'quote_type_id' => QuoteTypeId::Travel,
            'document_codes' => [
                DocumentTypeCode::CPS_TRVL,
            ],
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
            'is_required_for_send_policy' => 1,
        ],
        [
            'quote_type_id' => QuoteTypeId::Health,
            'document_codes' => [
                DocumentTypeCode::POLC,
                DocumentTypeCode::SMAF_HLTH,
                DocumentTypeCode::CPS,
                DocumentTypeCode::ECARD_HLTH,
            ],
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
            'is_required_for_send_policy' => 1,
        ],
        [
            'quote_type_id' => QuoteTypeId::Life,
            'document_codes' => [
                DocumentTypeCode::PS_LIFE,
                DocumentTypeCode::AC_LIFE,
                DocumentTypeCode::CPC,
                DocumentTypeCode::PHB,
            ],
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
            'is_required_for_send_policy' => 1,
        ],
        [
            'quote_type_id' => QuoteTypeId::Business,
            'document_codes' => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::CPC,
                DocumentTypeCode::PHB,
                DocumentTypeCode::COMP_PH,
                DocumentTypeCode::COMP_PS,
                DocumentTypeCode::COMP_PC,

            ],
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
            'is_required_for_send_policy' => 1,
        ],
        [
            'quote_type_id' => QuoteTypeId::Home,
            'document_codes' => [
                DocumentTypeCode::CPS,
            ],
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
            'is_required_for_send_policy' => 1,
        ],
        [
            'quote_type_id' => QuoteTypeId::Bike,
            'document_codes' => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::CPC,
                DocumentTypeCode::PHB,
            ],
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
            'is_required_for_send_policy' => 1,
        ],
        [
            'quote_type_id' => QuoteTypeId::Yacht,
            'document_codes' => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::PHB,
            ],
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
            'is_required_for_send_policy' => 1,
        ],
        [
            'quote_type_id' => QuoteTypeId::Pet,
            'document_codes' => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::PHB,
            ],
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
            'is_required_for_send_policy' => 1,
        ],
        [
            'quote_type_id' => QuoteTypeId::Cycle,
            'document_codes' => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::PHB,
            ],
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
            'is_required_for_send_policy' => 1,
        ],
        [
            'quote_type_id' => QuoteTypeId::Jetski,
            'document_codes' => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::PHB,
            ],
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
            'is_required_for_send_policy' => 1,
        ],
        [
            'quote_type_id' => QuoteTypeId::Business,
            'document_codes' => [
                DocumentTypeCode::GH_PS,
                DocumentTypeCode::GH_PC,
                DocumentTypeCode::GH_NL,
                DocumentTypeCode::GH_EC,
                DocumentTypeCode::PHB,
            ],
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
            'is_required_for_send_policy' => 1,
        ],
        [
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'document_codes' => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::CPC,
                DocumentTypeCode::PHB,
            ],
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
            'is_required_for_send_policy' => 1,
        ],
        [
            'quote_type_id' => QuoteTypeId::Device,
            'document_codes' => [
                DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_CERTIFICATE,
                DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_SCHEDULE,
                DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_HANDBOOK,
            ],
            'category' => DocumentTypeCategory::ISSUING_DOCUMENTS,
            'is_required_for_send_policy' => 1,
        ],
    ];
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->documentsRequiredForSendPolicy as $document) {
            foreach ($document['document_codes'] as $documentCode) {
                $documentType = DocumentType::where('quote_type_id', $document['quote_type_id'])
                    ->where('category', $document['category'])
                    ->where('code', $documentCode)
                    ->where('is_active', 1)
                    ->get();
                if ($documentType->count() > 0) {
                    foreach ($documentType as $documentType) {
                        $documentType->update([
                            'is_required_for_send_policy' => $document['is_required_for_send_policy'],
                        ]);
                    }
                }
            }
        }
    }
}
