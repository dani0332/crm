<?php

namespace Database\Seeders;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

    }

    private function cyberDocumentTypes(){
        $quoteDocuments = [
            [
                'code' => 'CYPDR',
                'text' => 'Receipt',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Cyber,
                'folder_path' => 'cyber',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => 4,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'CPD',
                'text' => 'Payment Proof',
                'description' => null,
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Cyber,
                'folder_path' => 'cyber',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'max_files' => 5,
                'max_size' => 30,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => 4,
                'receive_from_customer' => 1,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'CYDPDR',
                'text' => 'Discount Proof',
                'description' => null,
                'is_active' => 1,
                'quote_type_id' => QuoteTypeId::Cyber,
                'folder_path' => 'cyber',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'max_files' => 5,
                'max_size' => 30,
                'is_required' => 0,
                'send_to_customer' => 1,
                'sort_order' => 3,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
        ];
        foreach ($quoteDocuments as $document) {
            DocumentType::firstOrCreate(
                [
                    'code' => $document['code'],
                    'quote_type_id' => $document['quote_type_id'],
                ],
                $document
            );
        }
    }
}
