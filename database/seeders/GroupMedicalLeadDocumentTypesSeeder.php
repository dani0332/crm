<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Document types for Group Medical lead intake (e.g. IMCRM CTA uploads), scoped to business quote type
 * and {@see BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL} so they appear on AMT / Group Medical flows.
 */
class GroupMedicalLeadDocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        Model::unguarded(function (): void {
            foreach ($this->definitions() as $index => $definition) {
                $row = array_merge($this->baseAttributes(), $definition, [
                    'sort_order' => $index + 1,
                ]);

                DocumentType::firstOrCreate(
                    [
                        'code' => $row['code'],
                        'quote_type_id' => $row['quote_type_id'],
                        'business_type_of_insurance_id' => $row['business_type_of_insurance_id'],
                        'business_type_of_customer' => $row['business_type_of_customer'],
                    ],
                    $row
                );
            }
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            [
                'code' => DocumentTypeCode::CENSUS_LIST,
                'text' => 'Census List',
                'description' => 'Group medical census list uploaded with the lead.',
                'receive_from_customer' => 1,
            ],
            [
                'code' => DocumentTypeCode::CURRENT_TABLE_OF_BENEFITS,
                'text' => 'Current Table of Benefits',
                'description' => 'Current table of benefits for the group medical quote.',
                'receive_from_customer' => 0,
            ],
            [
                'code' => DocumentTypeCode::TRADE_LICENSE,
                'text' => 'Trade License',
                'description' => 'Trade license for the proposing company.',
                'receive_from_customer' => 0,
            ],
            [
                'code' => DocumentTypeCode::DHA_REPORT,
                'text' => 'DHA Report / Claim Report',
                'description' => 'Dubai Health Authority (DHA) or claim experience report.',
                'receive_from_customer' => 0,
            ],
            [
                'code' => DocumentTypeCode::OTHER_DOCUMENTS,
                'text' => 'Other Documents',
                'description' => 'Additional documents for the Group Medical lead.',
                'receive_from_customer' => 0,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baseAttributes(): array
    {
        return [
            'is_active' => 1,
            'quote_type_id' => QuoteTypeId::Business,
            'business_type_of_insurance_id' => BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL,
            'business_type_of_customer' => DocumentTypeCode::COMPANY_BUSINESS_TYPE_OF_CUSTOMER,
            'folder_path' => 'business',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 10,
            'max_size' => 25,
            'is_required' => 0,
            'send_to_customer' => 0,
            'category' => DocumentTypeCategory::QUOTE,
            'is_required_for_send_policy' => 0,
            'is_restricted_internal_document' => 0,
        ];
    }
}
