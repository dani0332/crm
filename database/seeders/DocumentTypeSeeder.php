<?php

namespace Database\Seeders;

use App\Enums\QuoteDocumentsEnum;
use App\Models\DocumentType;
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

        DocumentType::firstOrCreate(([
            'code' => QuoteDocumentsEnum::CAR_TAX_CREDIT,
            'category' => 'ISSUING_DOCUMENTS',
        ]), [
            'text' => 'Car Tax Credit Note',
            'is_active' => 1,
            'quote_type_id' => 1,
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'ISSUING_DOCUMENTS',
            'sort_order' => 13,
        ]);

        DocumentType::firstOrCreate(([
            'code' => QuoteDocumentsEnum::CAR_TAX_CREDIT_RAISE_BY_BUYER,
            'category' => 'ISSUING_DOCUMENTS',
        ]), [
            'text' => 'Car Tax Credit Note Raise By Buyer',
            'is_active' => 1,
            'quote_type_id' => 1,
            'folder_path' => 'car',
            'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 5,
            'max_size' => 25,
            'is_required' => 0,
            'category' => 'ISSUING_DOCUMENTS',
            'sort_order' => 13,
        ]);
    }
}
