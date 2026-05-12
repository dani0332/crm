<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocRequiredForPolicySendSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $documentTypes = [
            QuoteTypeId::Car => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::CPC,
            ],
            QuoteTypeId::Travel => [
                DocumentTypeCode::CPS_TRVL,
            ],
            QuoteTypeId::Health => [
                DocumentTypeCode::POLC,
                DocumentTypeCode::SMAF_HLTH,
                DocumentTypeCode::CPS,
                DocumentTypeCode::ECARD_HLTH,
            ],
            QuoteTypeId::Life => [
                DocumentTypeCode::PS_LIFE,
                DocumentTypeCode::AC_LIFE,
                DocumentTypeCode::CPC,
                DocumentTypeCode::PHB,
            ],
            QuoteTypeId::Home => [
                DocumentTypeCode::CPS,
            ],
            QuoteTypeId::Pet => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::PHB,
            ],
            QuoteTypeId::Bike => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::CPC,
                DocumentTypeCode::PHB,
            ],
            QuoteTypeId::Cycle => [
                DocumentTypeCode::PHB,
                DocumentTypeCode::CPS,
            ],
            QuoteTypeId::Yacht => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::PHB,
            ],
            QuoteTypeId::GroupMedical => [
                DocumentTypeCode::GH_PS,
                DocumentTypeCode::GH_PC,
                DocumentTypeCode::GH_NL,
                DocumentTypeCode::GH_EC,
            ],
            QuoteTypeId::CompanyCar => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::CPC,
                DocumentTypeCode::PHB,
            ],
            QuoteTypeId::Business => [
                DocumentTypeCode::GH_PS,
                DocumentTypeCode::GH_PC,
                DocumentTypeCode::GH_NL,
                DocumentTypeCode::GH_EC,
                DocumentTypeCode::PHB,
                DocumentTypeCode::COMP_PC,
                DocumentTypeCode::COMP_PS,
                DocumentTypeCode::COMP_PH,
            ],
            QuoteTypeId::Jetski => [
                DocumentTypeCode::CPS,
                DocumentTypeCode::PHB,
            ],
            QuoteTypeId::Device => [
                DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_CERTIFICATE,
                DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_SCHEDULE,
                DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_HANDBOOK,
            ],
        ];

        foreach ($documentTypes as $quoteTypeId => $documentTypeCodes) {
            foreach ($documentTypeCodes as $documentTypeCode) {
                $documentType = DocumentType::Active()->where('quote_type_id', $quoteTypeId)->where('code', $documentTypeCode)->first();
                if ($documentType) {
                    $documentType->is_required_for_send_policy = 1;
                    $documentType->save();
                }
            }
        }
    }
}
