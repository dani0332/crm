<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCode;
use App\Models\DocumentType;
use App\Models\QuoteType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $newDocumentTypes = [
            ['code' => 'OD', 'text' => 'Other Documents', 'folder_path' => 'quote_notes', 'accepted_files' => '.pdf,.xlsm,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png', 'max_files' => 20, 'max_size' => 10],
        ];

        foreach ($newDocumentTypes as $newDocumentType) {
            DocumentType::firstOrCreate(['code' => $newDocumentType['code']], [
                'text' => $newDocumentType['text'],
                'max_files' => $newDocumentType['max_files'],
                'max_size' => $newDocumentType['max_size'],
                'folder_path' => $newDocumentType['folder_path'],
                'accepted_files' => $newDocumentType['accepted_files'],
            ]);
        }
        $quoteTypes = QuoteType::all();
        foreach ($quoteTypes as $quoteType) {
            DocumentType::updateOrCreate(([
                'code' => DocumentTypeCode::PPR,
                'quote_type_id' => $quoteType->id,
            ]),
                [
                    'code' => DocumentTypeCode::PPR,
                    'text' => 'Payment Proforma Request',
                    'description' => '',
                    'quote_type_id' => $quoteType->id,
                    'folder_path' => strtolower($quoteType->code),
                    'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                    'max_files' => 10,
                    'max_size' => 25,
                    'is_active' => 1,
                    'is_required' => 0,
                    'send_to_customer' => 0,
                    'sort_order' => 14,
                    'receive_from_customer' => 0,
                    'category' => 'QUOTE',
                ]);
        }

    }
}
