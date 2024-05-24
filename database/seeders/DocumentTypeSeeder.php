<?php

namespace Database\Seeders;

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
    }
}
